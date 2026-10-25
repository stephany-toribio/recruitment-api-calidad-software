<?php

namespace Tests\MySql;

use App\Models\Candidato;
use App\Models\Empleo;
use App\Models\Empresa;
use App\Models\EstadoPostulacion;
use App\Services\Postulaciones\KanbanService;
use App\Services\Postulaciones\NotificacionPostulacionService;
use App\Services\Postulaciones\RetiroPostulacionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class RetirarPostulacionConcurrenciaMySqlTest extends TestCase
{
    private array $procesos = [];

    private array $registrosCreados = [];

    private bool $datosPreparados = false;

    private int $usuarioCandidatoId;

    private int $candidatoId;

    private int $vacanteId;

    private int $postulacionId;

    private int $estadoRecibidoId;

    private int $estadoContratadoId;

    protected function setUp(): void
    {
        parent::setUp();

        if (
            app()->configurationIsCached()
            || ! app()->environment('testing')
            || DB::connection()->getDriverName() !== 'mysql'
            || DB::selectOne('SELECT DATABASE() AS nombre')->nombre
                !== 'portalempleoshu04testing'
            || DB::transactionLevel() !== 0
        ) {
            throw new RuntimeException(
                'Estas pruebas requieren MySQL aislado y ninguna transacción exterior.'
            );
        }

        DB::transaction(function (): void {
            $this->prepararDatos();
        });

        $this->datosPreparados = true;
    }

    protected function tearDown(): void
    {
        try {
            foreach ($this->procesos as $worker) {
                if ($worker['proceso']->isRunning()) {
                    $worker['proceso']->stop(1);
                }

                $worker['entrada']->close();
            }

            if ($this->datosPreparados) {
                while (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }

                DB::transaction(function (): void {
                    $registros = array_reverse($this->registrosCreados);

                    foreach ($registros as [$tabla, $id]) {
                        if (in_array($tabla, ['roles', 'estados_postulacion'], true)) {
                            continue;
                        }

                        DB::table($tabla)
                            ->where('id', $id)
                            ->delete();
                    }

                    foreach ($registros as [$tabla, $id]) {
                        if (! in_array($tabla, ['roles', 'estados_postulacion'], true)) {
                            continue;
                        }

                        DB::table($tabla)
                            ->where('id', $id)
                            ->delete();
                    }
                });
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_dos_retiros_simultaneos_conservan_una_sola_fecha(): void
    {
        $primero = $this->iniciarWorker(true);
        $listoPrimero = $this->esperarEvento($primero, 'LISTO');

        $primero['entrada']->write("INICIAR\n");

        $this->esperarEvento(
            $primero,
            'POSTULACION_BLOQUEADA'
        );

        $segundo = $this->iniciarWorker(false);
        $listoSegundo = $this->esperarEvento($segundo, 'LISTO');

        $this->assertNotSame(
            $listoPrimero['conexion_id'],
            $listoSegundo['conexion_id']
        );

        $segundo['entrada']->write("INICIAR\n");

        $intento = $this->esperarEvento(
            $segundo,
            'INTENTO_CUENTA'
        );

        $this->assertGreaterThan(
            0,
            $intento['nivel_transaccion']
        );

        $this->assertNull(
            $this->buscarEvento(
                $segundo['proceso'],
                'RESULTADO'
            )
        );

        $primero['entrada']->write("LIBERAR\n");

        $resultadoPrimero = $this->terminarWorker($primero);
        $resultadoSegundo = $this->terminarWorker($segundo);

        $this->assertSame(200, $resultadoPrimero['status']);
        $this->assertSame(409, $resultadoSegundo['status']);
        $this->assertSame(
            'APPLICATION_ALREADY_WITHDRAWN',
            $resultadoSegundo['error_code']
        );

        $registro = DB::table('postulaciones')
            ->where('id', $this->postulacionId)
            ->first();

        $this->assertNotNull($registro);
        $this->assertNotNull($registro->retirada_en);

        $this->assertTrue(
            Carbon::parse(
                $registro->retirada_en,
                config('app.timezone')
            )->equalTo(
                Carbon::parse($resultadoPrimero['retirada_en'])
            )
        );

        $this->assertSame(
            $this->estadoRecibidoId,
            (int) $registro->estado_postulacion_id
        );

        $this->assertSame(
            1,
            DB::table('postulaciones')
                ->where('candidato_id', $this->candidatoId)
                ->where('empleo_id', $this->vacanteId)
                ->count()
        );
    }

    public function test_contratacion_confirmada_antes_del_bloqueo_impide_retirar(): void
    {
        $entrevistaId = $this->catalogoId(
            'estados_postulacion',
            EstadoPostulacion::ENTREVISTA
        );

        DB::table('postulaciones')
            ->where('id', $this->postulacionId)
            ->update([
                'estado_postulacion_id' => $entrevistaId,
                'updated_at' => now(),
            ]);

        $vacante = Empleo::findOrFail(
            $this->vacanteId
        );

        $empresa = Empresa::findOrFail(
            $vacante->empresa_id
        );

        $this->mock(
            NotificacionPostulacionService::class,
            function ($mock): void {
                $mock->shouldReceive('notificarDecision')
                    ->once()
                    ->andReturnNull();
            }
        );

        $worker = $this->iniciarWorker(false);
        $listo = $this->esperarEvento($worker, 'LISTO');

        $this->assertConexionIndependiente($listo);

        DB::beginTransaction();

        $resultadoKanban = app(
            KanbanService::class
        )->contratar(
            $empresa,
            $this->vacanteId,
            $this->postulacionId
        );

        $this->assertSame(
            'contratado',
            $resultadoKanban['estado']['codigo']
        );

        $movimientos = DB::table('historial_postulaciones')
            ->where('postulacion_id', $this->postulacionId)
            ->get(['id', 'estado_postulacion_id']);

        foreach ($movimientos as $movimiento) {
            $this->registrosCreados[] = [
                'historial_postulaciones',
                (int) $movimiento->id,
            ];
        }

        $this->assertCount(1, $movimientos);

        $this->assertSame(
            $this->estadoContratadoId,
            (int) $movimientos->first()->estado_postulacion_id
        );

        $worker['entrada']->write("INICIAR\n");

        $intento = $this->esperarEvento(
            $worker,
            'INTENTO_POSTULACION'
        );

        $this->assertGreaterThan(
            0,
            $intento['nivel_transaccion']
        );

        $this->assertNull(
            $this->buscarEvento(
                $worker['proceso'],
                'POSTULACION_BLOQUEADA'
            )
        );

        DB::commit();

        $resultadoRetiro = $this->terminarWorker($worker);

        $this->assertSame(409, $resultadoRetiro['status']);

        $this->assertSame(
            'APPLICATION_NOT_WITHDRAWABLE',
            $resultadoRetiro['error_code']
        );

        $this->assertDatabaseHas('postulaciones', [
            'id' => $this->postulacionId,
            'estado_postulacion_id' => $this->estadoContratadoId,
            'retirada_en' => null,
        ]);

        $this->assertSame(
            1,
            DB::table('historial_postulaciones')
                ->where('postulacion_id', $this->postulacionId)
                ->count()
        );
    }

    public function test_suspension_confirmada_antes_del_bloqueo_impide_retirar(): void
    {
        $worker = $this->iniciarWorker(false);
        $listo = $this->esperarEvento($worker, 'LISTO');

        $this->assertConexionIndependiente($listo);

        DB::beginTransaction();

        DB::table('usuarios')
            ->where('id', $this->usuarioCandidatoId)
            ->update(['estado_cuenta' => 'suspendido']);

        $worker['entrada']->write("INICIAR\n");

        $intento = $this->esperarEvento(
            $worker,
            'INTENTO_CUENTA'
        );

        $this->assertGreaterThan(
            0,
            $intento['nivel_transaccion']
        );

        $this->assertNull(
            $this->buscarEvento(
                $worker['proceso'],
                'RESULTADO'
            )
        );

        DB::commit();

        $resultado = $this->terminarWorker($worker);

        $this->assertSame(403, $resultado['status']);
        $this->assertSame(
            'FORBIDDEN',
            $resultado['error_code']
        );

        $this->assertDatabaseHas('postulaciones', [
            'id' => $this->postulacionId,
            'estado_postulacion_id' => $this->estadoRecibidoId,
            'retirada_en' => null,
        ]);
    }

    private function assertConexionIndependiente(array $listo): void
    {
        $principal = (int) DB::selectOne(
            'SELECT CONNECTION_ID() AS id'
        )->id;

        $this->assertNotSame(
            $principal,
            $listo['conexion_id']
        );
    }

    private function iniciarWorker(bool $detener): array
    {
        $entrada = new InputStream;

        $entrada->write(json_encode([
            'candidato_id' => $this->candidatoId,
            'postulacion_id' => $this->postulacionId,
            'detener_tras_bloqueo' => $detener,
        ], JSON_THROW_ON_ERROR).PHP_EOL);

        $configuracion = DB::connection()->getConfig();

        $proceso = new Process(
            [
                PHP_BINARY,
                base_path('tests/Support/hu16_concurrencia_worker.php'),
            ],
            base_path(),
            [
                'APP_ENV' => 'testing',
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => (string) $configuracion['host'],
                'DB_PORT' => (string) $configuracion['port'],
                'DB_DATABASE' => 'portalempleoshu04testing',
                'DB_USERNAME' => (string) $configuracion['username'],
                'DB_PASSWORD' => (string) $configuracion['password'],
                'DB_URL' => '',
                'CACHE_STORE' => 'array',
                'SESSION_DRIVER' => 'array',
                'QUEUE_CONNECTION' => 'sync',
            ],
            $entrada,
            30
        );

        $worker = [
            'proceso' => $proceso,
            'entrada' => $entrada,
        ];

        $this->procesos[] = $worker;
        $proceso->start();

        return $worker;
    }

    private function esperarEvento(
        array $worker,
        string $nombre
    ): array {
        $proceso = $worker['proceso'];

        $evento = $this->buscarEvento($proceso, $nombre);

        if ($evento !== null) {
            return $evento;
        }

        $encontrado = $proceso->waitUntil(
            function (
                string $tipo,
                string $salida
            ) use ($proceso, $nombre): bool {
                return $this->buscarEvento(
                    $proceso,
                    $nombre
                ) !== null;
            }
        );

        $evento = $this->buscarEvento($proceso, $nombre);

        if (! $encontrado || $evento === null) {
            throw new RuntimeException(
                "No llegó el evento {$nombre}."
                .PHP_EOL.$proceso->getErrorOutput()
                .PHP_EOL.$proceso->getOutput()
            );
        }

        return $evento;
    }

    private function buscarEvento(
        Process $proceso,
        string $nombre
    ): ?array {
        foreach (explode("\n", $proceso->getOutput()) as $linea) {
            $datos = json_decode(trim($linea), true);

            if (
                is_array($datos)
                && ($datos['evento'] ?? null) === $nombre
            ) {
                return $datos;
            }
        }

        return null;
    }

    private function terminarWorker(array $worker): array
    {
        $worker['entrada']->close();
        $worker['proceso']->wait();

        $this->assertTrue(
            $worker['proceso']->isSuccessful(),
            $worker['proceso']->getErrorOutput()
                .PHP_EOL.$worker['proceso']->getOutput()
        );

        $resultado = $this->buscarEvento(
            $worker['proceso'],
            'RESULTADO'
        );

        if ($resultado === null) {
            throw new RuntimeException(
                'El proceso terminó sin resultado.'
            );
        }

        return $resultado;
    }

    private function prepararDatos(): void
    {
        $this->usuarioCandidatoId = $this->crearUsuario('candidato');

        $this->candidatoId = $this->insertar('candidatos', [
            'usuario_id' => $this->usuarioCandidatoId,
            'nombre' => 'Candidato concurrencia',
            'apellidos' => 'HU16',
            'dni' => null,
            'fecha_nacimiento' => '2000-01-01',
            'genero' => null,
            'busca_primer_empleo' => true,
            'celular' => '9'.str_pad(
                (string) $this->usuarioCandidatoId,
                8,
                '0',
                STR_PAD_LEFT
            ),
        ]);

        $empresaId = $this->insertar('empresas', [
            'usuario_id' => $this->crearUsuario('empresa'),
            'encargado_empresa' => 'Responsable concurrencia HU16',
            'razon_social' => 'Concurrencia HU16 '
                .bin2hex(random_bytes(6)),
            'sector' => 'Tecnología e Informática',
            'descripcion' => 'Empresa para comprobar retiros concurrentes.',
            'ruc' => '20'.random_int(100000000, 999999999),
            'sitio_web' => null,
        ]);

        $areaId = $this->insertar('areas_estudio', [
            'nombre' => 'Concurrencia HU16 '
                .bin2hex(random_bytes(6)),
            'activo' => true,
        ]);

        $nivelId = DB::table('niveles_puesto')
            ->whereNull('candidato_id')
            ->where('estado', 'activo')
            ->where('nombre', 'Junior')
            ->value('id');

        if ($nivelId === null) {
            throw new RuntimeException(
                'Falta el nivel global activo Junior.'
            );
        }

        $hoy = now('America/Lima');

        $this->vacanteId = $this->insertar('empleos', [
            'empresa_id' => $empresaId,
            'titulo' => 'Vacante concurrencia HU16',
            'descripcion' => 'Vacante exclusiva para comprobar retiros mediante conexiones MySQL independientes.',
            'nivel_puesto_id' => $nivelId,
            'area_estudio_id' => $areaId,
            'tipo_empleo' => 'remoto',
            'ubicacion' => null,
            'salario_min' => null,
            'salario_max' => null,
            'fecha_inicio_vigencia' => $hoy->toDateString(),
            'fecha_fin_vigencia' => $hoy->copy()
                ->addDay()
                ->toDateString(),
            'estado_empleo' => 'activa',
            'numero_vacantes' => 1,
        ]);

        $this->estadoRecibidoId = $this->catalogoId(
            'estados_postulacion',
            EstadoPostulacion::RECIBIDO
        );

        $this->estadoContratadoId = $this->catalogoId(
            'estados_postulacion',
            EstadoPostulacion::CONTRATADO
        );

        $this->postulacionId = $this->insertar('postulaciones', [
            'candidato_id' => $this->candidatoId,
            'empleo_id' => $this->vacanteId,
            'estado_postulacion_id' => $this->estadoRecibidoId,
            'porcentaje_match' => null,
            'fecha_postulacion' => now(),
            'retirada_en' => null,
        ]);
    }

    private function crearUsuario(string $rol): int
    {
        return $this->insertar('usuarios', [
            'rol_id' => $this->catalogoId('roles', $rol),
            'correo' => 'hu16-concurrencia-'
                .bin2hex(random_bytes(10)).'@example.test',
            'password' => Hash::make(bin2hex(random_bytes(16))),
            'estado_cuenta' => 'activo',
        ]);
    }

    private function catalogoId(
        string $tabla,
        string $nombre
    ): int {
        $id = DB::table($tabla)
            ->where('nombre', $nombre)
            ->value('id');

        return $id === null
            ? $this->insertar($tabla, ['nombre' => $nombre])
            : (int) $id;
    }

    private function insertar(
        string $tabla,
        array $datos
    ): int {
        $id = (int) DB::table($tabla)->insertGetId([
            'created_at' => now(),
            'updated_at' => now(),
            ...$datos,
        ]);

        $this->registrosCreados[] = [$tabla, $id];

        return $id;
    }

    public function test_retiro_confirmado_mientras_kanban_espera_impide_contratar(): void
    {
        $entrevistaId = $this->catalogoId(
            'estados_postulacion',
            EstadoPostulacion::ENTREVISTA
        );

        DB::table('postulaciones')
            ->where('id', $this->postulacionId)
            ->update([
                'estado_postulacion_id' => $entrevistaId,
                'updated_at' => now(),
            ]);

        $empresaId = (int) DB::table('empleos')
            ->where('id', $this->vacanteId)
            ->value('empresa_id');

        $entrada = new InputStream;

        $entrada->write(json_encode([
            'empresa_id' => $empresaId,
            'vacante_id' => $this->vacanteId,
            'postulacion_id' => $this->postulacionId,
        ], JSON_THROW_ON_ERROR).PHP_EOL);

        $configuracion = DB::connection()->getConfig();

        $proceso = new Process(
            [
                PHP_BINARY,
                base_path('tests/Support/hu16_kanban_worker.php'),
            ],
            base_path(),
            [
                'APP_ENV' => 'testing',
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => (string) $configuracion['host'],
                'DB_PORT' => (string) $configuracion['port'],
                'DB_DATABASE' => 'portalempleoshu04testing',
                'DB_USERNAME' => (string) $configuracion['username'],
                'DB_PASSWORD' => (string) $configuracion['password'],
                'DB_URL' => '',
                'CACHE_STORE' => 'array',
                'SESSION_DRIVER' => 'array',
                'QUEUE_CONNECTION' => 'sync',
                'MAIL_MAILER' => 'array',
            ],
            $entrada,
            30
        );

        $worker = [
            'proceso' => $proceso,
            'entrada' => $entrada,
        ];

        $this->procesos[] = $worker;
        $proceso->start();

        $listo = $this->esperarEvento($worker, 'LISTO');
        $this->assertConexionIndependiente($listo);

        DB::beginTransaction();

        $retirada = app(
            RetiroPostulacionService::class
        )->retirar(
            Candidato::findOrFail($this->candidatoId),
            $this->postulacionId
        );

        $this->assertTrue($retirada->estaRetirada());

        $registroRetirado = (array) DB::table('postulaciones')
            ->where('id', $this->postulacionId)
            ->first();

        $worker['entrada']->write("INICIAR\n");

        $intento = $this->esperarEvento(
            $worker,
            'INTENTO_POSTULACION'
        );

        $this->assertGreaterThan(
            0,
            $intento['nivel_transaccion']
        );

        $this->assertNull(
            $this->buscarEvento($proceso, 'RESULTADO')
        );

        DB::commit();

        $resultado = $this->terminarWorker($worker);

        $this->assertSame(409, $resultado['status']);

        $this->assertSame(
            'APPLICATION_WITHDRAWN',
            $resultado['error_code']
        );

        $this->assertSame(
            $registroRetirado,
            (array) DB::table('postulaciones')
                ->where('id', $this->postulacionId)
                ->first()
        );

        $this->assertSame(
            $entrevistaId,
            (int) $registroRetirado['estado_postulacion_id']
        );

        $this->assertSame(
            0,
            DB::table('historial_postulaciones')
                ->where('postulacion_id', $this->postulacionId)
                ->count()
        );

        $this->assertSame(
            0,
            DB::table('notificaciones_postulacion')
                ->where('postulacion_id', $this->postulacionId)
                ->count()
        );
    }
}
