<?php

namespace Tests\MySql;

use App\Models\Empleo;
use App\Services\Vacantes\DisponibilidadVacanteService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DisponibilidadVacanteMySqlTest extends TestCase
{
    private Empleo $vacante;

    private int $usuarioEmpresaId;

    private int $nivelId;

    private int $areaId;

    private bool $transaccionIniciada = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertFalse($this->app->configurationIsCached());
        $this->assertTrue($this->app->environment('testing'));
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame(
            'portalempleoshu04testing',
            DB::selectOne('SELECT DATABASE() AS nombre')->nombre
        );

        Carbon::setTestNow(
            Carbon::parse('2026-10-24 12:00:00', 'America/Lima')
        );

        DB::beginTransaction();
        $this->transaccionIniciada = true;

        try {
            $ahora = now();
            $etiqueta = bin2hex(random_bytes(6));

            $this->usuarioEmpresaId = $this->crearUsuario('empresa');
            $adminId = $this->crearUsuario('admin');

            $empresaId = DB::table('empresas')->insertGetId([
                'usuario_id' => $this->usuarioEmpresaId,
                'encargado_empresa' => 'Responsable de prueba',
                'razon_social' => 'Empresa disponibilidad '.$etiqueta,
                'sector' => 'Tecnología e Informática',
                'ruc' => '20'.random_int(100000000, 999999999),
                'sitio_web' => null,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            $nivelId = DB::table('niveles_puesto')
                ->whereNull('candidato_id')
                ->where('estado', 'activo')
                ->where('nombre', 'Junior')
                ->value('id');

            $this->assertNotNull($nivelId);
            $this->nivelId = (int) $nivelId;

            $this->areaId = DB::table('areas_estudio')->insertGetId([
                'nombre' => 'Área disponibilidad '.$etiqueta,
                'activo' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            $this->vacante = Empleo::create([
                'empresa_id' => $empresaId,
                'titulo' => 'Vacante disponible de prueba',
                'descripcion' => str_repeat('Descripción de prueba. ', 4),
                'nivel_puesto_id' => $this->nivelId,
                'area_estudio_id' => $this->areaId,
                'tipo_empleo' => 'remoto',
                'ubicacion' => null,
                'salario_min' => null,
                'salario_max' => null,
                'fecha_inicio_vigencia' => '2026-10-24',
                'fecha_fin_vigencia' => '2026-10-25',
                'estado_empleo' => 'activa',
                'numero_vacantes' => 2,
            ]);

            DB::table('revisiones_vacante')->insert([
                'empleo_id' => $this->vacante->id,
                'admin_id' => $adminId,
                'decision' => 'aprobada',
                'motivo' => null,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            DB::table('pagos_vacante')->insert([
                'empleo_id' => $this->vacante->id,
                'resultado' => 'exitoso',
                'monto' => '200.00',
                'moneda' => 'PEN',
                'tarjeta_ultimos4' => '1111',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        } catch (\Throwable $error) {
            DB::rollBack();
            $this->transaccionIniciada = false;
            Carbon::setTestNow();

            throw $error;
        }
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

        return DB::table('usuarios')->insertGetId([
            'rol_id' => $rolId,
            'correo' => bin2hex(random_bytes(12)).'@example.test',
            'password' => Hash::make(bin2hex(random_bytes(24))),
            'estado_cuenta' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function comprobarDisponibilidad(bool $esperada): void
    {
        $servicio = $this->app->make(DisponibilidadVacanteService::class);

        $this->assertSame(
            $esperada,
            $servicio->estaDisponible($this->vacante)
        );

        $ids = $servicio
            ->aplicar(Empleo::withTrashed())
            ->whereKey($this->vacante->id)
            ->pluck('empleos.id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $this->assertSame(
            $esperada ? [(int) $this->vacante->id] : [],
            $ids
        );
    }

    public function test_incluye_vacante_que_cumple_todas_las_condiciones(): void
    {
        $this->comprobarDisponibilidad(true);
    }

    #[DataProvider('condicionesQueBloquean')]
    public function test_excluye_vacante_tras_cambio_directo_en_bd(
        string $caso
    ): void {
        $id = $this->vacante->id;

        switch ($caso) {
            case 'sin_revision':
                DB::table('revisiones_vacante')
                    ->where('empleo_id', $id)
                    ->delete();
                break;

            case 'no_aprobada':
                DB::table('revisiones_vacante')
                    ->where('empleo_id', $id)
                    ->update([
                        'decision' => 'no_aprobada',
                        'motivo' => 'Motivo de prueba',
                    ]);
                break;

            case 'sin_pago':
                DB::table('pagos_vacante')
                    ->where('empleo_id', $id)
                    ->delete();
                break;

            case 'pago_fallido':
                DB::table('pagos_vacante')
                    ->where('empleo_id', $id)
                    ->update(['resultado' => 'fallido']);
                break;

            case 'pago_cancelado':
                DB::table('pagos_vacante')
                    ->where('empleo_id', $id)
                    ->update([
                        'resultado' => 'cancelado',
                        'tarjeta_ultimos4' => null,
                    ]);
                break;

            case 'pausada':
                DB::table('empleos')
                    ->where('id', $id)
                    ->update(['estado_empleo' => 'pausada']);
                break;

            case 'finalizada':
                DB::table('empleos')
                    ->where('id', $id)
                    ->update(['estado_empleo' => 'finalizada']);
                break;

            case 'eliminada':
                DB::table('empleos')
                    ->where('id', $id)
                    ->update(['deleted_at' => now()]);
                break;

            case 'empresa_suspendida':
                DB::table('usuarios')
                    ->where('id', $this->usuarioEmpresaId)
                    ->update(['estado_cuenta' => 'suspendido']);
                break;

            case 'por_iniciar':
                DB::table('empleos')
                    ->where('id', $id)
                    ->update([
                        'fecha_inicio_vigencia' => '2026-10-25',
                        'fecha_fin_vigencia' => '2026-10-26',
                    ]);
                break;

            case 'vencida':
                DB::table('empleos')
                    ->where('id', $id)
                    ->update([
                        'fecha_inicio_vigencia' => '2026-10-20',
                        'fecha_fin_vigencia' => '2026-10-23',
                    ]);
                break;

            default:
                $this->fail('Caso de prueba no implementado.');
        }

        $this->comprobarDisponibilidad(false);
    }

    public static function condicionesQueBloquean(): array
    {
        return [
            'pendiente de revision' => ['sin_revision'],
            'no aprobada' => ['no_aprobada'],
            'sin pago' => ['sin_pago'],
            'solo pago fallido' => ['pago_fallido'],
            'solo pago cancelado' => ['pago_cancelado'],
            'pausada' => ['pausada'],
            'finalizada' => ['finalizada'],
            'eliminada logicamente' => ['eliminada'],
            'empresa suspendida' => ['empresa_suspendida'],
            'vigencia futura' => ['por_iniciar'],
            'vigencia vencida' => ['vencida'],
        ];
    }

    #[DataProvider('limitesDeVigencia')]
    public function test_respeta_dias_completos_en_lima(
        string $instanteUtc,
        bool $disponible
    ): void {
        Carbon::setTestNow(Carbon::parse($instanteUtc, 'UTC'));

        $this->comprobarDisponibilidad($disponible);
    }

    public static function limitesDeVigencia(): array
    {
        return [
            'un segundo antes del inicio en Lima' => [
                '2026-10-24 04:59:59', false,
            ],
            'inicio a medianoche en Lima' => [
                '2026-10-24 05:00:00', true,
            ],
            'UTC cambia de dia pero Lima sigue en el inicial' => [
                '2026-10-25 00:00:00', true,
            ],
            'ultimo segundo del dia final en Lima' => [
                '2026-10-26 04:59:59', true,
            ],
            'primer segundo posterior al dia final en Lima' => [
                '2026-10-26 05:00:00', false,
            ],
        ];
    }

    public function test_detecta_suspension_aunque_el_modelo_tenga_relaciones_cargadas(): void
    {
        $this->vacante->load(['empresa.usuario', 'revision', 'pagos']);

        DB::table('usuarios')
            ->where('id', $this->usuarioEmpresaId)
            ->update(['estado_cuenta' => 'suspendido']);

        $this->assertSame(
            'activo',
            $this->vacante->empresa->usuario->estado_cuenta
        );

        $this->comprobarDisponibilidad(false);
    }

    public function test_desactivar_catalogos_no_oculta_vacantes_existentes(): void
    {
        DB::table('niveles_puesto')
            ->where('id', $this->nivelId)
            ->update(['estado' => 'inactivo']);

        DB::table('areas_estudio')
            ->where('id', $this->areaId)
            ->update(['activo' => false]);

        $this->comprobarDisponibilidad(true);
    }

    public function test_un_intento_fallido_no_anula_un_pago_exitoso(): void
    {
        DB::table('pagos_vacante')->insert([
            'empleo_id' => $this->vacante->id,
            'resultado' => 'fallido',
            'monto' => '200.00',
            'moneda' => 'PEN',
            'tarjeta_ultimos4' => '0002',
            'created_at' => now()->addSecond(),
            'updated_at' => now()->addSecond(),
        ]);

        $this->comprobarDisponibilidad(true);
    }

    public function test_modelo_no_guardado_no_esta_disponible(): void
    {
        $servicio = $this->app->make(DisponibilidadVacanteService::class);

        $this->assertFalse($servicio->estaDisponible(new Empleo));
    }
}
