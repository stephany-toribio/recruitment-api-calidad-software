<?php

namespace Tests\MySql;

use App\Exceptions\BusinessException;
use App\Models\Candidato;
use App\Models\Empleo;
use App\Models\EstadoPostulacion;
use App\Models\Postulacion;
use App\Services\Postulaciones\PostulacionService;
use App\Services\Vacantes\BusquedaVacantesService;
use App\Services\Vacantes\DetalleVacanteService;
use App\Services\Vacantes\DisponibilidadVacanteService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class VacanteCompletaMySqlTest extends TestCase
{
    private bool $transaccionIniciada = false;

    private Candidato $candidato;

    private Empleo $vacante;

    protected function setUp(): void
    {
        parent::setUp();

        if (
            app()->configurationIsCached()
            || ! app()->environment('testing')
            || DB::connection()->getDriverName() !== 'mysql'
            || DB::selectOne('SELECT DATABASE() AS nombre')->nombre
                !== 'portalempleoshu04testing'
        ) {
            throw new RuntimeException(
                'Estas pruebas requieren la base MySQL aislada portalempleoshu04testing.'
            );
        }

        DB::beginTransaction();
        $this->transaccionIniciada = true;

        Carbon::setTestNow(
            Carbon::create(2026, 10, 25, 12, 0, 0, 'America/Lima')
        );

        $this->prepararDatos();
    }

    protected function tearDown(): void
    {
        try {
            if ($this->transaccionIniciada) {
                DB::rollBack();
            }
        } finally {
            Carbon::setTestNow();

            parent::tearDown();
        }
    }

    public function test_con_un_puesto_libre_sigue_disponible_y_admite_postulaciones(): void
    {
        $this->crearPostulacion(EstadoPostulacion::CONTRATADO);

        $this->assertTrue($this->disponible());
        $this->assertTrue($this->apareceEnBusqueda());

        $postulacion = $this->postular();

        $this->assertSame(
            (int) $this->vacante->getKey(),
            (int) $postulacion->empleo_id
        );
    }

    public function test_los_rechazados_no_ocupan_puestos(): void
    {
        $this->crearPostulacion(EstadoPostulacion::CONTRATADO);
        $this->crearPostulacion(EstadoPostulacion::RECHAZADO);
        $this->crearPostulacion(EstadoPostulacion::RECHAZADO);

        $this->assertTrue($this->disponible());
        $this->assertTrue($this->apareceEnBusqueda());
    }

    public function test_con_todos_los_puestos_cubiertos_no_aparece_en_la_busqueda(): void
    {
        $this->cubrirPuestos();

        $this->assertFalse($this->disponible());
        $this->assertFalse($this->apareceEnBusqueda());
    }

    public function test_con_todos_los_puestos_cubiertos_no_admite_postulaciones(): void
    {
        $this->cubrirPuestos();

        try {
            $this->postular();
        } catch (BusinessException $error) {
            $this->assertSame(404, $error->getStatusCode());
            $this->assertSame('VACANCY_NOT_AVAILABLE', $error->getErrorCode());
            $this->assertFalse(
                Postulacion::query()
                    ->where('candidato_id', $this->candidato->getKey())
                    ->exists()
            );

            return;
        }

        $this->fail('La postulación debía rechazarse con 404 VACANCY_NOT_AVAILABLE.');
    }

    public function test_con_todos_los_puestos_cubiertos_el_detalle_pasa_a_modo_historico(): void
    {
        $this->cubrirPuestos();

        $detalle = app(DetalleVacanteService::class)->disponible(
            (int) $this->vacante->getKey(),
            $this->candidato
        );

        $this->assertSame('historico', $detalle['modo_consulta']);
        $this->assertFalse($detalle['puede_postular']);
        $this->assertSame('VACANCY_UNAVAILABLE', $detalle['motivo_no_postulable']);
    }

    private function disponible(): bool
    {
        return app(DisponibilidadVacanteService::class)
            ->estaDisponible($this->vacante);
    }

    private function apareceEnBusqueda(): bool
    {
        return app(BusquedaVacantesService::class)
            ->construirConsulta([])
            ->whereKey($this->vacante->getKey())
            ->exists();
    }

    private function postular(): Postulacion
    {
        return app(PostulacionService::class)->postular(
            $this->candidato,
            (int) $this->vacante->getKey()
        );
    }

    private function cubrirPuestos(): void
    {
        for ($i = 0; $i < $this->vacante->numero_vacantes; $i++) {
            $this->crearPostulacion(EstadoPostulacion::CONTRATADO);
        }
    }

    private function crearPostulacion(string $estado): Postulacion
    {
        return Postulacion::create([
            'candidato_id' => $this->crearCandidato()->getKey(),
            'empleo_id' => $this->vacante->getKey(),
            'estado_postulacion_id' => $this->estadoId($estado),
            'fecha_postulacion' => now()->subDay(),
            'retirada_en' => null,
        ]);
    }

    private function prepararDatos(): void
    {
        $this->candidato = $this->crearCandidato();

        $empresaId = DB::table('empresas')->insertGetId([
            'usuario_id' => $this->crearUsuario('empresa'),
            'encargado_empresa' => 'Responsable vacante completa',
            'razon_social' => 'Empresa completa '.bin2hex(random_bytes(6)),
            'sector' => 'Tecnología e Informática',
            'descripcion' => null,
            'ruc' => '20'.random_int(100000000, 999999999),
            'sitio_web' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $nivelId = DB::table('niveles_puesto')
            ->whereNull('candidato_id')
            ->where('estado', 'activo')
            ->where('nombre', 'Junior')
            ->value('id');

        if ($nivelId === null) {
            throw new RuntimeException('Falta el nivel global activo Junior.');
        }

        $areaId = DB::table('areas_estudio')->insertGetId([
            'nombre' => 'Área completa '.bin2hex(random_bytes(6)),
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->vacante = Empleo::create([
            'empresa_id' => $empresaId,
            'titulo' => 'Vacante con puestos limitados',
            'descripcion' => 'Buscamos personas para el equipo de soporte y atención de incidencias de la empresa.',
            'nivel_puesto_id' => $nivelId,
            'area_estudio_id' => $areaId,
            'tipo_empleo' => 'remoto',
            'ubicacion' => null,
            'salario_min' => null,
            'salario_max' => null,
            'fecha_inicio_vigencia' => '2026-10-25',
            'fecha_fin_vigencia' => '2026-10-30',
            'estado_empleo' => Empleo::ACTIVA,
            'numero_vacantes' => 2,
        ]);

        DB::table('revisiones_vacante')->insert([
            'empleo_id' => $this->vacante->getKey(),
            'admin_id' => $this->crearUsuario('admin'),
            'decision' => 'aprobada',
            'motivo' => null,
            'invalidada_en' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pagos_vacante')->insert([
            'empleo_id' => $this->vacante->getKey(),
            'resultado' => 'exitoso',
            'monto' => 200,
            'moneda' => 'PEN',
            'tarjeta_ultimos4' => '1111',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->estadoId(EstadoPostulacion::RECIBIDO);
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
        }

        return (int) DB::table('usuarios')->insertGetId([
            'rol_id' => $rolId,
            'correo' => 'completa-'.bin2hex(random_bytes(10)).'@example.test',
            'password' => Hash::make(bin2hex(random_bytes(16))),
            'estado_cuenta' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearCandidato(): Candidato
    {
        $usuarioId = $this->crearUsuario('candidato');

        return Candidato::create([
            'usuario_id' => $usuarioId,
            'nombre' => 'Candidato de prueba',
            'apellidos' => 'Vacante completa',
            'dni' => null,
            'fecha_nacimiento' => '2000-01-01',
            'genero' => null,
            'busca_primer_empleo' => true,
            'celular' => '9'.str_pad((string) $usuarioId, 8, '0', STR_PAD_LEFT),
        ]);
    }

    private function estadoId(string $nombre): int
    {
        $id = DB::table('estados_postulacion')
            ->where('nombre', $nombre)
            ->value('id');

        if ($id === null) {
            $id = DB::table('estados_postulacion')->insertGetId([
                'nombre' => $nombre,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return (int) $id;
    }
}
