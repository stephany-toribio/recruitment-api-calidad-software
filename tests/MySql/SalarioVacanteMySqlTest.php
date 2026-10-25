<?php

namespace Tests\MySql;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SalarioVacanteMySqlTest extends TestCase
{
    private int $empresaId;

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

        DB::beginTransaction();
        $this->transaccionIniciada = true;

        try {
            $ahora = now();
            $etiqueta = bin2hex(random_bytes(6));

            $rolId = DB::table('roles')
                ->where('nombre', 'empresa')
                ->value('id');

            if ($rolId === null) {
                $rolId = DB::table('roles')->insertGetId([
                    'nombre' => 'empresa',
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }

            $usuarioId = DB::table('usuarios')->insertGetId([
                'rol_id' => $rolId,
                'correo' => 'hu04-'.$etiqueta.'@example.test',
                'password' => Hash::make(bin2hex(random_bytes(24))),
                'estado_cuenta' => 'activo',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            $this->empresaId = DB::table('empresas')->insertGetId([
                'usuario_id' => $usuarioId,
                'encargado_empresa' => 'Responsable de prueba HU04',
                'razon_social' => 'Empresa de prueba '.$etiqueta,
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

            $this->assertNotNull(
                $nivelId,
                'Debe existir el nivel global Junior habilitado.'
            );

            $this->nivelId = (int) $nivelId;

            $this->areaId = DB::table('areas_estudio')->insertGetId([
                'nombre' => 'Área de prueba HU04 '.$etiqueta,
                'activo' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

        } catch (\Throwable $error) {
            DB::rollBack();
            $this->transaccionIniciada = false;

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
            parent::tearDown();
        }
    }

    private function datosVacante(): array
    {
        return [
            'empresa_id' => $this->empresaId,
            'titulo' => 'Vacante de prueba HU04',
            'descripcion' => str_repeat('Descripción de prueba. ', 4),
            'nivel_puesto_id' => $this->nivelId,
            'area_estudio_id' => $this->areaId,
            'salario_min' => null,
            'salario_max' => null,
            'fecha_inicio_vigencia' => '2026-10-24',
            'fecha_fin_vigencia' => '2026-11-24',
            'estado_empleo' => 'activa',
            'tipo_empleo' => 'remoto',
            'ubicacion' => null,
            'numero_vacantes' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    #[DataProvider('casosSalario')]
    public function test_mysql_valida_el_rango_salarial(
        ?string $minimo,
        ?string $maximo,
        bool $aceptado
    ): void {
        $datos = array_replace($this->datosVacante(), [
            'salario_min' => $minimo,
            'salario_max' => $maximo,
        ]);

        if ($aceptado) {
            $id = DB::table('empleos')->insertGetId($datos);

            $vacante = DB::table('empleos')->where('id', $id)->first();

            $this->assertNotNull($vacante);
            $this->assertSame($minimo, $vacante->salario_min);
            $this->assertSame($maximo, $vacante->salario_max);

            return;
        }

        $cantidadAntes = DB::table('empleos')->count();

        try {
            DB::table('empleos')->insert($datos);
        } catch (QueryException $error) {
            $this->assertSame(3819, (int) ($error->errorInfo[1] ?? 0));
            $this->assertStringContainsString(
                'chk_empleos_salarios',
                $error->getMessage()
            );
            $this->assertSame(
                $cantidadAntes,
                DB::table('empleos')->count()
            );

            return;
        }

        $this->fail('MySQL aceptó un rango salarial inválido.');
    }

    public static function casosSalario(): array
    {
        return [
            'ambos ausentes' => [null, null, true],
            'rango valido' => ['2000.00', '4000.00', true],
            'importes iguales' => ['3000.00', '3000.00', true],
            'solo minimo' => ['2000.00', null, false],
            'solo maximo' => [null, '4000.00', false],
            'minimo cero' => ['0.00', '4000.00', false],
            'minimo negativo' => ['-1.00', '4000.00', false],
            'rango invertido' => ['4000.00', '2000.00', false],
            'maximo negativo' => ['2000.00', '-1.00', false],
        ];
    }

    #[DataProvider('casosModalidadUbicacion')]
    public function test_mysql_valida_modalidad_y_ubicacion(
        ?string $modalidad,
        ?string $ubicacion,
        bool $aceptado
    ): void {
        $id = DB::table('empleos')->insertGetId($this->datosVacante());

        $cambios = [
            'tipo_empleo' => $modalidad,
            'ubicacion' => $ubicacion,
        ];

        if ($aceptado) {
            DB::table('empleos')->where('id', $id)->update($cambios);

            $vacante = DB::table('empleos')->where('id', $id)->first();

            $this->assertNotNull($vacante);
            $this->assertSame($modalidad, $vacante->tipo_empleo);
            $this->assertSame($ubicacion, $vacante->ubicacion);

            return;
        }

        try {
            DB::table('empleos')->where('id', $id)->update($cambios);
        } catch (QueryException $error) {
            $this->assertSame(3819, (int) ($error->errorInfo[1] ?? 0));
            $this->assertStringContainsString(
                'chk_empleos_modalidad_ubicacion',
                $error->getMessage()
            );

            $vacante = DB::table('empleos')->where('id', $id)->first();

            $this->assertNotNull($vacante);
            $this->assertSame('remoto', $vacante->tipo_empleo);
            $this->assertNull($vacante->ubicacion);

            return;
        }

        $this->fail('MySQL aceptó modalidad o ubicación inválida.');
    }

    public static function casosModalidadUbicacion(): array
    {
        return [
            'remoto sin ubicacion' => [
                'remoto', null, true,
            ],
            'remoto con ubicacion' => [
                'remoto', 'Lima, Perú', true,
            ],
            'presencial con ubicacion' => [
                'presencial', 'Los Olivos, Lima', true,
            ],
            'hibrido con ubicacion' => [
                'hibrido', 'San Borja, Lima', true,
            ],
            'modalidad ausente' => [
                null, 'Lima', false,
            ],
            'presencial sin ubicacion' => [
                'presencial', null, false,
            ],
            'hibrido sin ubicacion' => [
                'hibrido', null, false,
            ],
            'presencial ubicacion vacia' => [
                'presencial', '', false,
            ],
            'hibrido ubicacion vacia' => [
                'hibrido', '', false,
            ],
            'remoto ubicacion vacia' => [
                'remoto', '', false,
            ],
            'presencial solo espacios' => [
                'presencial', '   ', false,
            ],
            'hibrido solo espacios' => [
                'hibrido', '   ', false,
            ],
            'remoto solo espacios' => [
                'remoto', '   ', false,
            ],
            'presencial solo tabulacion' => [
                'presencial', "\t", false,
            ],
            'hibrido solo salto de linea' => [
                'hibrido', "\n", false,
            ],
            'remoto espacios combinados' => [
                'remoto', " \t\n ", false,
            ],
        ];
    }

    public function test_mysql_rechaza_vacante_sin_area(): void
    {
        $datos = array_replace($this->datosVacante(), [
            'area_estudio_id' => null,
        ]);

        $cantidadAntes = DB::table('empleos')->count();

        try {
            DB::table('empleos')->insert($datos);
        } catch (QueryException $error) {
            $this->assertSame(3819, (int) ($error->errorInfo[1] ?? 0));
            $this->assertStringContainsString(
                'chk_empleos_area_obligatoria',
                $error->getMessage()
            );
            $this->assertSame(
                $cantidadAntes,
                DB::table('empleos')->count()
            );

            return;
        }

        $this->fail('MySQL aceptó una vacante sin área.');
    }

    public function test_mysql_rechaza_actualizar_a_un_area_inexistente(): void
    {
        $id = DB::table('empleos')->insertGetId($this->datosVacante());

        $inexistente = (int) DB::table('areas_estudio')->max('id') + 1000;

        try {
            DB::table('empleos')
                ->where('id', $id)
                ->update(['area_estudio_id' => $inexistente]);
        } catch (QueryException $error) {
            $this->assertSame(1452, (int) ($error->errorInfo[1] ?? 0));
            $this->assertSame(
                $this->areaId,
                (int) DB::table('empleos')
                    ->where('id', $id)
                    ->value('area_estudio_id')
            );

            return;
        }

        $this->fail('MySQL aceptó un área inexistente.');
    }

    private function crearNivelPersonalizado(): int
    {
        $ahora = now();
        $etiqueta = bin2hex(random_bytes(6));

        $rolId = DB::table('roles')
            ->where('nombre', 'candidato')
            ->value('id');

        if ($rolId === null) {
            $rolId = DB::table('roles')->insertGetId([
                'nombre' => 'candidato',
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);
        }

        $usuarioId = DB::table('usuarios')->insertGetId([
            'rol_id' => $rolId,
            'correo' => 'candidato-'.$etiqueta.'@example.test',
            'password' => Hash::make(bin2hex(random_bytes(24))),
            'estado_cuenta' => 'activo',
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);

        $candidatoId = DB::table('candidatos')->insertGetId([
            'usuario_id' => $usuarioId,
            'nombre' => 'Candidato',
            'apellidos' => 'Prueba HU04',
            'celular' => '9'.random_int(10000000, 99999999),
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);

        return DB::table('niveles_puesto')->insertGetId([
            'candidato_id' => $candidatoId,
            'nombre' => 'Nivel personalizado '.$etiqueta,
            'estado' => 'activo',
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ]);
    }

    private function comprobarRechazoMysql(
        callable $operacion,
        int $codigo,
        string $referencia
    ): void {
        try {
            $operacion();
        } catch (QueryException $error) {
            $this->assertSame(
                $codigo,
                (int) ($error->errorInfo[1] ?? 0)
            );

            $this->assertStringContainsString(
                $referencia,
                $error->getMessage()
            );

            return;
        }

        $this->fail('MySQL aceptó una operación que debía rechazar.');
    }

    public function test_mysql_acepta_nivel_global_y_calcula_dueno_cero(): void
    {
        $id = DB::table('empleos')->insertGetId($this->datosVacante());

        $vacante = DB::table('empleos')->where('id', $id)->first();

        $this->assertNotNull($vacante);
        $this->assertSame($this->nivelId, (int) $vacante->nivel_puesto_id);
        $this->assertSame(0, (int) $vacante->nivel_puesto_dueno_id);
    }

    public function test_mysql_rechaza_insertar_vacante_con_nivel_personalizado(): void
    {
        $personalizadoId = $this->crearNivelPersonalizado();

        $datos = array_replace($this->datosVacante(), [
            'nivel_puesto_id' => $personalizadoId,
        ]);

        $cantidadAntes = DB::table('empleos')->count();

        $this->comprobarRechazoMysql(
            fn () => DB::table('empleos')->insert($datos),
            1452,
            'empleos_nivel_global_foreign'
        );

        $this->assertSame(
            $cantidadAntes,
            DB::table('empleos')->count()
        );
    }

    public function test_mysql_rechaza_cambiar_vacante_a_nivel_personalizado(): void
    {
        $personalizadoId = $this->crearNivelPersonalizado();
        $id = DB::table('empleos')->insertGetId($this->datosVacante());

        $this->comprobarRechazoMysql(
            fn () => DB::table('empleos')
                ->where('id', $id)
                ->update(['nivel_puesto_id' => $personalizadoId]),
            1452,
            'empleos_nivel_global_foreign'
        );

        $this->assertSame(
            $this->nivelId,
            (int) DB::table('empleos')
                ->where('id', $id)
                ->value('nivel_puesto_id')
        );
    }

    public function test_mysql_impide_escribir_el_dueno_calculado(): void
    {
        $id = DB::table('empleos')->insertGetId($this->datosVacante());

        $this->comprobarRechazoMysql(
            fn () => DB::table('empleos')
                ->where('id', $id)
                ->update(['nivel_puesto_dueno_id' => 123]),
            3105,
            'nivel_puesto_dueno_id'
        );

        $this->assertSame(
            0,
            (int) DB::table('empleos')
                ->where('id', $id)
                ->value('nivel_puesto_dueno_id')
        );
    }

    public function test_mysql_permite_desactivar_nivel_sin_perder_vacantes(): void
    {
        $id = DB::table('empleos')->insertGetId($this->datosVacante());

        DB::table('niveles_puesto')
            ->where('id', $this->nivelId)
            ->update(['estado' => 'inactivo']);

        $this->assertSame(
            'inactivo',
            DB::table('niveles_puesto')
                ->where('id', $this->nivelId)
                ->value('estado')
        );

        $this->assertSame(
            $this->nivelId,
            (int) DB::table('empleos')
                ->where('id', $id)
                ->value('nivel_puesto_id')
        );
    }

    public function test_mysql_impide_personalizar_un_nivel_global_utilizado(): void
    {
        $personalizadoId = $this->crearNivelPersonalizado();

        $candidatoId = DB::table('niveles_puesto')
            ->where('id', $personalizadoId)
            ->value('candidato_id');

        DB::table('empleos')->insertGetId($this->datosVacante());

        $this->comprobarRechazoMysql(
            fn () => DB::table('niveles_puesto')
                ->where('id', $this->nivelId)
                ->update(['candidato_id' => $candidatoId]),
            1451,
            'empleos_nivel_global_foreign'
        );

        $this->assertNull(
            DB::table('niveles_puesto')
                ->where('id', $this->nivelId)
                ->value('candidato_id')
        );
    }
}
