<?php

namespace Tests\MySql;

use App\Models\EstadoPostulacion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Process;
use Tests\TestCase;

class PostularVacanteConcurrenciaMySqlTest extends TestCase
{
    private array $procesos = [];

    private array $usuariosCreados = [];

    private array $rolesCreados = [];

    private array $estadosCreados = [];

    private ?int $candidatoId = null;

    private array $candidatosAdicionales = [];

    private ?int $empresaId = null;

    private ?int $areaId = null;

    private ?int $vacanteId = null;

    private bool $datosPreparados = false;

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

                $this->limpiarDatos();
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_dos_procesos_del_mismo_candidato_crean_una_sola_postulacion(): void
    {
        $primero = $this->iniciarWorker(true);
        $listoPrimero = $this->esperarEvento($primero, 'LISTO');

        $primero['entrada']->write("INICIAR\n");

        $this->esperarEvento($primero, 'BLOQUEO_OBTENIDO');

        $segundo = $this->iniciarWorker(false);
        $listoSegundo = $this->esperarEvento($segundo, 'LISTO');

        $this->assertNotSame(
            $listoPrimero['conexion_id'],
            $listoSegundo['conexion_id']
        );

        $segundo['entrada']->write("INICIAR\n");

        $intento = $this->esperarEvento(
            $segundo,
            'INTENTO_BLOQUEO'
        );

        $this->assertGreaterThan(
            0,
            $intento['nivel_transaccion']
        );

        $this->assertNull(
            $this->buscarEvento(
                $segundo['proceso'],
                'BLOQUEO_OBTENIDO'
            )
        );

        $primero['entrada']->write("LIBERAR\n");

        $resultadoPrimero = $this->terminarWorker($primero);
        $resultadoSegundo = $this->terminarWorker($segundo);

        $this->assertSame(201, $resultadoPrimero['status']);
        $this->assertSame(409, $resultadoSegundo['status']);
        $this->assertSame(
            'DUPLICATE_APPLICATION',
            $resultadoSegundo['error_code']
        );

        $this->assertSame(
            1,
            DB::table('postulaciones')
                ->where('candidato_id', $this->candidatoId)
                ->where('empleo_id', $this->vacanteId)
                ->count()
        );

        $this->assertDatabaseHas('postulaciones', [
            'id' => $resultadoPrimero['postulacion_id'],
            'candidato_id' => $this->candidatoId,
            'empleo_id' => $this->vacanteId,
            'estado_postulacion_id' => $this->estadoId(),
            'retirada_en' => null,
        ]);
    }

    public function test_pausa_confirmada_antes_de_obtener_el_bloqueo_impide_postular(): void
    {
        $worker = $this->iniciarWorker(false);
        $listo = $this->esperarEvento($worker, 'LISTO');

        $conexionPrincipal = (int) DB::selectOne(
            'SELECT CONNECTION_ID() AS id'
        )->id;

        $this->assertNotSame(
            $conexionPrincipal,
            $listo['conexion_id']
        );

        DB::beginTransaction();

        DB::table('empleos')
            ->where('id', $this->vacanteId)
            ->lockForUpdate()
            ->first();

        DB::table('empleos')
            ->where('id', $this->vacanteId)
            ->update(['estado_empleo' => 'pausada']);

        $worker['entrada']->write("INICIAR\n");

        $intento = $this->esperarEvento(
            $worker,
            'INTENTO_BLOQUEO'
        );

        $this->assertGreaterThan(
            0,
            $intento['nivel_transaccion']
        );

        $this->assertNull(
            $this->buscarEvento(
                $worker['proceso'],
                'BLOQUEO_OBTENIDO'
            )
        );

        DB::commit();

        $resultado = $this->terminarWorker($worker);

        $this->assertSame(404, $resultado['status']);
        $this->assertSame(
            'VACANCY_NOT_AVAILABLE',
            $resultado['error_code']
        );

        $this->assertDatabaseMissing('postulaciones', [
            'empleo_id' => $this->vacanteId,
        ]);

        $this->assertDatabaseHas('empleos', [
            'id' => $this->vacanteId,
            'estado_empleo' => 'pausada',
        ]);
    }

        #[\PHPUnit\Framework\Attributes\DataProvider(
        'cuentasSuspendidasConcurrentemente'
    )]
    public function test_suspension_confirmada_durante_la_postulacion_impide_registrar(
        string $tipoCuenta,
        int $estadoEsperado,
        string $codigoEsperado
    ): void {
        $usuarioId = $tipoCuenta === 'candidato'
            ? DB::table('candidatos')
                ->where('id', $this->candidatoId)
                ->value('usuario_id')
            : DB::table('empresas')
                ->where('id', $this->empresaId)
                ->value('usuario_id');

        $this->assertNotNull($usuarioId);

        $worker = $this->iniciarWorker(false);
        $listo = $this->esperarEvento($worker, 'LISTO');

        $conexionPrincipal = (int) DB::selectOne(
            'SELECT CONNECTION_ID() AS id'
        )->id;

        $this->assertNotSame(
            $conexionPrincipal,
            $listo['conexion_id']
        );

        DB::beginTransaction();

        $cambios = DB::table('usuarios')
            ->where('id', $usuarioId)
            ->where('estado_cuenta', 'activo')
            ->update(['estado_cuenta' => 'suspendido']);

        $this->assertSame(1, $cambios);

        $worker['entrada']->write("INICIAR\n");

        $this->esperarEvento($worker, 'BLOQUEO_OBTENIDO');

        $intento = $this->esperarEvento(
            $worker,
            'INTENTO_CUENTAS'
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

        $this->assertSame(
            $estadoEsperado,
            $resultado['status']
        );

        $this->assertSame(
            $codigoEsperado,
            $resultado['error_code']
        );

        $this->assertDatabaseMissing('postulaciones', [
            'empleo_id' => $this->vacanteId,
        ]);

        $this->assertDatabaseHas('usuarios', [
            'id' => $usuarioId,
            'estado_cuenta' => 'suspendido',
        ]);
    }

    public static function cuentasSuspendidasConcurrentemente(): array
    {
        return [
            'suspension del candidato' => [
                'candidato',
                403,
                'FORBIDDEN',
            ],
            'suspension de la empresa' => [
                'empresa',
                404,
                'VACANCY_NOT_AVAILABLE',
            ],
        ];
    }

    private function iniciarWorker(
        bool $detener,
        ?int $candidatoId = null,
        string $modo = 'servicio'
    ): array {
        $candidatoId ??= $this->candidatoId;

        $token = null;

        if ($modo === 'http') {
            $usuarioId = DB::table('candidatos')
                ->where('id', $candidatoId)
                ->value('usuario_id');

            $token = \App\Models\Usuario::findOrFail($usuarioId)
                ->createToken('qa08-concurrencia')
                ->plainTextToken;
        }

        $entrada = new InputStream;

        $entrada->write(json_encode([
            'candidato_id' => $candidatoId,
            'vacante_id' => $this->vacanteId,
            'detener_tras_bloqueo' => $detener,
            'modo' => $modo,
            'token' => $token,
        ], JSON_THROW_ON_ERROR).PHP_EOL);

        $configuracion = DB::connection()->getConfig();

        $proceso = new Process(
            [
                PHP_BINARY,
                base_path('tests/Support/hu05_concurrencia_worker.php'),
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
        $usuarioCandidatoId = $this->crearUsuario('candidato');

        $this->candidatoId = (int) DB::table('candidatos')
            ->insertGetId([
                'usuario_id' => $usuarioCandidatoId,
                'nombre' => 'Candidato concurrencia',
                'apellidos' => 'HU05',
                'dni' => null,
                'fecha_nacimiento' => '2000-01-01',
                'genero' => null,
                'busca_primer_empleo' => true,
                'celular' => '9'.str_pad(
                    (string) $usuarioCandidatoId,
                    8,
                    '0',
                    STR_PAD_LEFT
                ),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $usuarioEmpresaId = $this->crearUsuario('empresa');
        $adminId = $this->crearUsuario('admin');

        $this->empresaId = (int) DB::table('empresas')
            ->insertGetId([
                'usuario_id' => $usuarioEmpresaId,
                'encargado_empresa' => 'Responsable concurrencia',
                'razon_social' => 'Concurrencia HU05 '
                    .bin2hex(random_bytes(6)),
                'sector' => 'Tecnología e Informática',
                'descripcion' => 'Empresa para pruebas concurrentes.',
                'ruc' => '20'.random_int(100000000, 999999999),
                'sitio_web' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $this->areaId = (int) DB::table('areas_estudio')
            ->insertGetId([
                'nombre' => 'Concurrencia HU05 '
                    .bin2hex(random_bytes(6)),
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
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

        $this->vacanteId = (int) DB::table('empleos')
            ->insertGetId([
                'empresa_id' => $this->empresaId,
                'titulo' => 'Vacante concurrencia HU05',
                'descripcion' => 'Vacante exclusiva para comprobar postulaciones mediante conexiones independientes.',
                'nivel_puesto_id' => $nivelId,
                'area_estudio_id' => $this->areaId,
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
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        DB::table('revisiones_vacante')->insert([
            'empleo_id' => $this->vacanteId,
            'admin_id' => $adminId,
            'decision' => 'aprobada',
            'motivo' => null,
            'invalidada_en' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pagos_vacante')->insert([
            'empleo_id' => $this->vacanteId,
            'resultado' => 'exitoso',
            'monto' => 200,
            'moneda' => 'PEN',
            'tarjeta_ultimos4' => '1111',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->estadoId();
    }

    private function crearUsuario(string $rol): int
    {
        $rolId = DB::table('roles')
            ->where('nombre', $rol)
            ->value('id');

        if ($rolId === null) {
            $rolId = DB::table('roles')->insertGetId([
                'nombre' => $rol,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->rolesCreados[] = (int) $rolId;
        }

        $usuarioId = (int) DB::table('usuarios')
            ->insertGetId([
                'rol_id' => $rolId,
                'correo' => 'hu05-concurrencia-'
                    .bin2hex(random_bytes(10)).'@example.test',
                'password' => Hash::make(
                    bin2hex(random_bytes(16))
                ),
                'estado_cuenta' => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        $this->usuariosCreados[] = $usuarioId;

        return $usuarioId;
    }

    private function estadoId(): int
    {
        $id = DB::table('estados_postulacion')
            ->where('nombre', EstadoPostulacion::RECIBIDO)
            ->value('id');

        if ($id === null) {
            $id = DB::table('estados_postulacion')
                ->insertGetId([
                    'nombre' => EstadoPostulacion::RECIBIDO,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            $this->estadosCreados[] = (int) $id;
        }

        return (int) $id;
    }

    private function limpiarDatos(): void
    {
        DB::transaction(function (): void {
            DB::table('postulaciones')
                ->where('empleo_id', $this->vacanteId)
                ->delete();

            DB::table('pagos_vacante')
                ->where('empleo_id', $this->vacanteId)
                ->delete();

            DB::table('revisiones_vacante')
                ->where('empleo_id', $this->vacanteId)
                ->delete();

            DB::table('empleos')
                ->where('id', $this->vacanteId)
                ->delete();

            DB::table('empresas')
                ->where('id', $this->empresaId)
                ->delete();

            DB::table('areas_estudio')
                ->where('id', $this->areaId)
                ->delete();

            DB::table('personal_access_tokens')
                ->where('tokenable_type', (new \App\Models\Usuario)->getMorphClass())
                ->whereIn('tokenable_id', $this->usuariosCreados)
                ->delete();

            DB::table('candidatos')
                ->whereIn('id', array_merge(
                    [$this->candidatoId],
                    $this->candidatosAdicionales
                ))
                ->delete();

            DB::table('usuarios')
                ->whereIn('id', $this->usuariosCreados)
                ->delete();

            DB::table('estados_postulacion')
                ->whereIn('id', $this->estadosCreados)
                ->delete();

            DB::table('roles')
                ->whereIn('id', $this->rolesCreados)
                ->delete();
        });
    }

    private function crearCandidatoAdicional(): int
    {
        $usuarioId = $this->crearUsuario('candidato');

        $id = (int) DB::table('candidatos')->insertGetId([
            'usuario_id' => $usuarioId,
            'nombre' => 'Candidato adicional QA08',
            'apellidos' => 'Integracion',
            'dni' => null,
            'fecha_nacimiento' => '2000-01-01',
            'genero' => null,
            'busca_primer_empleo' => true,
            'celular' => '9'.str_pad(
                (string) $usuarioId,
                8,
                '0',
                STR_PAD_LEFT
            ),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->candidatosAdicionales[] = $id;

        return $id;
    }

    private function carreraHttp(int $segundoCandidato): array
    {
        $primero = $this->iniciarWorker(
            true,
            $this->candidatoId,
            'http'
        );

        $listoPrimero = $this->esperarEvento(
            $primero,
            'LISTO'
        );

        $primero['entrada']->write("INICIAR\n");

        $this->esperarEvento(
            $primero,
            'BLOQUEO_OBTENIDO'
        );

        $segundo = $this->iniciarWorker(
            false,
            $segundoCandidato,
            'http'
        );

        $listoSegundo = $this->esperarEvento(
            $segundo,
            'LISTO'
        );

        $this->assertNotSame(
            $listoPrimero['conexion_id'],
            $listoSegundo['conexion_id']
        );

        $segundo['entrada']->write("INICIAR\n");

        $intento = $this->esperarEvento(
            $segundo,
            'INTENTO_BLOQUEO'
        );

        $this->assertGreaterThan(
            0,
            $intento['nivel_transaccion']
        );

        $this->assertNull(
            $this->buscarEvento(
                $segundo['proceso'],
                'BLOQUEO_OBTENIDO'
            )
        );

        $primero['entrada']->write("LIBERAR\n");

        return [
            $this->terminarWorker($primero),
            $this->terminarWorker($segundo),
        ];
    }

    public function test_cp13_02_concurrencia_http_mismo_candidato(): void
    {
        [$primero, $segundo] = $this->carreraHttp(
            $this->candidatoId
        );

        $this->assertSame('http', $primero['modo']);
        $this->assertSame('http', $segundo['modo']);

        $this->assertSame(201, $primero['status']);
        $this->assertSame(409, $segundo['status']);

        $this->assertTrue($primero['success']);
        $this->assertFalse($segundo['success']);

        $this->assertSame(
            'DUPLICATE_APPLICATION',
            $segundo['error_code']
        );

        $this->assertSame('recibido', $primero['estado']);

        $this->assertSame(
            1,
            DB::table('postulaciones')
                ->where('empleo_id', $this->vacanteId)
                ->where('candidato_id', $this->candidatoId)
                ->count()
        );

        $this->assertDatabaseHas('postulaciones', [
            'id' => $primero['postulacion_id'],
            'empleo_id' => $this->vacanteId,
            'candidato_id' => $this->candidatoId,
            'estado_postulacion_id' => $this->estadoId(),
            'retirada_en' => null,
        ]);

        fwrite(STDOUT, PHP_EOL.json_encode([
            'caso' => 'CP-13-02',
            'status' => [
                $primero['status'],
                $segundo['status'],
            ],
            'error_code' => $segundo['error_code'],
            'registros' => 1,
        ], JSON_THROW_ON_ERROR).PHP_EOL);
    }

    public function test_cp14_01_concurrencia_http_candidatos_distintos(): void
    {
        $otro = $this->crearCandidatoAdicional();

        $this->assertDatabaseHas('empleos', [
            'id' => $this->vacanteId,
            'numero_vacantes' => 1,
        ]);

        [$primero, $segundo] = $this->carreraHttp($otro);

        $this->assertSame('http', $primero['modo']);
        $this->assertSame('http', $segundo['modo']);

        $this->assertSame(201, $primero['status']);
        $this->assertSame(201, $segundo['status']);

        $this->assertTrue($primero['success']);
        $this->assertTrue($segundo['success']);

        $this->assertSame('recibido', $primero['estado']);
        $this->assertSame('recibido', $segundo['estado']);

        $this->assertNotSame(
            $primero['postulacion_id'],
            $segundo['postulacion_id']
        );

        $this->assertSame(
            2,
            DB::table('postulaciones')
                ->where('empleo_id', $this->vacanteId)
                ->count()
        );

        foreach ([
            [$primero, $this->candidatoId],
            [$segundo, $otro],
        ] as [$respuesta, $candidato]) {
            $this->assertDatabaseHas('postulaciones', [
                'id' => $respuesta['postulacion_id'],
                'empleo_id' => $this->vacanteId,
                'candidato_id' => $candidato,
                'estado_postulacion_id' => $this->estadoId(),
                'retirada_en' => null,
            ]);
        }

        fwrite(STDOUT, PHP_EOL.json_encode([
            'caso' => 'CP-14-01',
            'status' => [
                $primero['status'],
                $segundo['status'],
            ],
            'numero_vacantes' => 1,
            'registros' => 2,
        ], JSON_THROW_ON_ERROR).PHP_EOL);
    }

}
