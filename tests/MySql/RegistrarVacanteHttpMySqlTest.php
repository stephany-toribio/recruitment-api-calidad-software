<?php

namespace Tests\MySql;

use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RegistrarVacanteHttpMySqlTest extends TestCase
{
    private const RUTA = '/api/v1/empresas/me/vacantes';

    private Usuario $empresaUsuario;

    private string $token;

    private int $empresaId;

    private int $nivelId;

    private int $areaId;

    private int $habilidadId;

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

            $this->empresaUsuario = $this->crearUsuario('empresa');

            $this->empresaId = DB::table('empresas')->insertGetId([
                'usuario_id' => $this->empresaUsuario->id,
                'encargado_empresa' => 'Responsable de prueba',
                'razon_social' => 'Empresa HTTP '.$etiqueta,
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
                'nombre' => 'Área HTTP '.$etiqueta,
                'activo' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            $this->habilidadId = DB::table('habilidades')->insertGetId([
                'nombre' => 'Habilidad HTTP '.$etiqueta,
                'tipo_habilidad' => 'blanda',
                'area_estudio_id' => null,
                'activo' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]);

            $this->token = $this->empresaUsuario
                ->createToken('prueba-hu04')
                ->plainTextToken;
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

    private function crearUsuario(string $rol): Usuario
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

        return Usuario::create([
            'rol_id' => $rolId,
            'correo' => bin2hex(random_bytes(12)).'@example.test',
            'password' => bin2hex(random_bytes(24)),
            'estado_cuenta' => 'activo',
        ]);
    }

    private function datosValidos(): array
    {
        $hoy = now('America/Lima');

        return [
            'titulo' => 'Desarrollador de prueba',
            'descripcion' => str_repeat('Descripción de la vacante. ', 4),
            'nivel_puesto_id' => $this->nivelId,
            'area_estudio_id' => $this->areaId,
            'tipo_empleo' => 'remoto',
            'fecha_inicio_vigencia' => $hoy->toDateString(),
            'fecha_fin_vigencia' => $hoy->copy()
                ->addDays(7)
                ->toDateString(),
            'max_contrataciones' => 2,
            'habilidades' => [
                [
                    'habilidad_id' => $this->habilidadId,
                    'requisito' => 'must_have',
                ],
            ],
        ];
    }

    private function prepararToken(?string $token): void
    {
        $this->app->make('auth')->forgetGuards();
        $this->withoutHeader('Authorization');

        if ($token !== null) {
            $this->withHeader('Authorization', 'Bearer '.$token);
        }
    }

    public function test_registra_remoto_sin_salario_y_conserva_null(): void
    {
        $this->prepararToken($this->token);

        $respuesta = $this->postJson(self::RUTA, $this->datosValidos());

        $respuesta
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.nivel_puesto.id', $this->nivelId)
            ->assertJsonPath('data.nivel_puesto.nombre', 'Junior')
            ->assertJsonPath('data.area_profesional.id', $this->areaId)
            ->assertJsonPath('data.tipo_empleo', 'remoto')
            ->assertJsonPath('data.ubicacion', null)
            ->assertJsonPath('data.salario_min', null)
            ->assertJsonPath('data.salario_max', null)
            ->assertJsonPath('data.estado.revision', 'pendiente')
            ->assertJsonPath('data.estado.pago', 'sin_pago')
            ->assertJsonPath('data.estado.general', 'pendiente_revision');

        $id = $respuesta->json('data.id');

        $this->assertIsInt($id);

        $this->assertDatabaseHas('empleos', [
            'id' => $id,
            'empresa_id' => $this->empresaId,
            'nivel_puesto_id' => $this->nivelId,
            'area_estudio_id' => $this->areaId,
            'salario_min' => null,
            'salario_max' => null,
        ]);

        $this->assertDatabaseHas('empleo_habilidades', [
            'empleo_id' => $id,
            'habilidad_id' => $this->habilidadId,
            'tipo_requisito' => 'must_have',
        ]);

        $this->assertDatabaseMissing('revisiones_vacante', [
            'empleo_id' => $id,
        ]);

        $this->assertDatabaseMissing('pagos_vacante', [
            'empleo_id' => $id,
        ]);

        $data = $respuesta->json('data');

        $this->assertArrayNotHasKey('nivel_experiencia', $data);
        $this->assertArrayNotHasKey('nivel_puesto_dueno_id', $data);
    }

    public function test_registra_presencial_con_ubicacion_y_salario(): void
    {
        $this->prepararToken($this->token);

        $datos = array_replace($this->datosValidos(), [
            'tipo_empleo' => 'presencial',
            'ubicacion' => '  Los Olivos, Lima  ',
            'salario_min' => '2000.00',
            'salario_max' => '4000.00',
        ]);

        $respuesta = $this->postJson(self::RUTA, $datos);

        $respuesta
            ->assertCreated()
            ->assertJsonPath('data.tipo_empleo', 'presencial')
            ->assertJsonPath('data.ubicacion', 'Los Olivos, Lima');

        $this->assertEquals(2000, $respuesta->json('data.salario_min'));
        $this->assertEquals(4000, $respuesta->json('data.salario_max'));

        $this->assertDatabaseHas('empleos', [
            'id' => $respuesta->json('data.id'),
            'tipo_empleo' => 'presencial',
            'ubicacion' => 'Los Olivos, Lima',
            'salario_min' => '2000.00',
            'salario_max' => '4000.00',
        ]);
    }

    public function test_rechaza_hibrido_sin_ubicacion_sin_crear_vacante(): void
    {
        $datos = array_replace($this->datosValidos(), [
            'tipo_empleo' => 'hibrido',
        ]);

        $this->comprobarRegistroRechazado($datos, 'ubicacion');
    }

    public function test_sin_token_responde_401(): void
    {
        $this->prepararToken(null);

        $cantidadAntes = DB::table('empleos')->count();

        $this->postJson(self::RUTA, $this->datosValidos())
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');

        $this->assertSame($cantidadAntes, DB::table('empleos')->count());
    }

    public function test_candidato_con_token_real_recibe_403(): void
    {
        $candidato = $this->crearUsuario('candidato');
        $token = $candidato->createToken('prueba')->plainTextToken;

        $this->prepararToken($token);

        $cantidadAntes = DB::table('empleos')->count();

        $this->postJson(self::RUTA, $this->datosValidos())
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');

        $this->assertSame($cantidadAntes, DB::table('empleos')->count());
    }

    public function test_suspension_directa_bloquea_token_existente(): void
    {
        DB::table('usuarios')
            ->where('id', $this->empresaUsuario->id)
            ->update(['estado_cuenta' => 'suspendido']);

        $this->prepararToken($this->token);

        $cantidadAntes = DB::table('empleos')->count();

        $this->postJson(self::RUTA, $this->datosValidos())
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');

        $this->assertSame($cantidadAntes, DB::table('empleos')->count());
    }

    public function test_catalogo_devuelve_solo_niveles_globales_habilitados(): void
    {
        $inactivoId = DB::table('niveles_puesto')->insertGetId([
            'nombre' => 'Nivel inactivo '.bin2hex(random_bytes(6)),
            'estado' => 'inactivo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $respuesta = $this->getJson('/api/v1/niveles-experiencia');

        $respuesta
            ->assertOk()
            ->assertJsonPath('success', true);

        $esperados = DB::table('niveles_puesto')
            ->whereNull('candidato_id')
            ->where('estado', 'activo')
            ->orderBy('orden')
            ->orderBy('id')
            ->get(['id', 'nombre'])
            ->map(fn ($nivel) => [
                'id' => (int) $nivel->id,
                'nombre' => $nivel->nombre,
            ])
            ->all();

        $this->assertSame($esperados, $respuesta->json('data'));
        $this->assertNotContains(
            $inactivoId,
            array_column($respuesta->json('data'), 'id')
        );
    }

    private function comprobarRegistroRechazado(
        array $datos,
        string $campo
    ): void {
        $this->prepararToken($this->token);

        $tablas = [
            'empleos',
            'empleo_habilidades',
            'revisiones_vacante',
            'pagos_vacante',
        ];

        $cantidades = [];

        foreach ($tablas as $tabla) {
            $cantidades[$tabla] = DB::table($tabla)->count();
        }

        $this->postJson(self::RUTA, $datos)
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors([$campo]);

        foreach ($cantidades as $tabla => $cantidad) {
            $this->assertSame(
                $cantidad,
                DB::table($tabla)->count(),
                'Se modificó la cantidad de registros en '.$tabla
            );
        }
    }

    #[DataProvider('entradasInvalidas')]
    public function test_rechaza_entradas_invalidas_sin_crear_registros(
        array $cambios,
        string $campo
    ): void {
        if ($campo === 'habilidades.0.requisito') {
            $cambios['habilidades'][0]['habilidad_id'] = $this->habilidadId;
        }

        $datos = array_replace($this->datosValidos(), $cambios);

        $this->comprobarRegistroRechazado($datos, $campo);
    }

    public static function entradasInvalidas(): array
    {
        return [
            'titulo demasiado largo' => [
                ['titulo' => str_repeat('a', 101)],
                'titulo',
            ],
            'titulo solo espacios' => [
                ['titulo' => '   '],
                'titulo',
            ],
            'descripcion corta' => [
                ['descripcion' => 'Muy corta'],
                'descripcion',
            ],
            'descripcion solo espacios' => [
                ['descripcion' => str_repeat(' ', 60)],
                'descripcion',
            ],
            'modalidad desconocida' => [
                ['tipo_empleo' => 'virtual'],
                'tipo_empleo',
            ],
            'modalidad como lista' => [
                ['tipo_empleo' => ['remoto']],
                'tipo_empleo',
            ],
            'presencial sin ubicacion' => [
                ['tipo_empleo' => 'presencial'],
                'ubicacion',
            ],
            'hibrido ubicacion solo espacios' => [
                ['tipo_empleo' => 'hibrido', 'ubicacion' => '   '],
                'ubicacion',
            ],
            'solo salario minimo' => [
                ['salario_min' => '2000.00'],
                'salario_max',
            ],
            'solo salario maximo' => [
                ['salario_max' => '4000.00'],
                'salario_min',
            ],
            'salario minimo cero' => [
                ['salario_min' => '0.00', 'salario_max' => '4000.00'],
                'salario_min',
            ],
            'salario minimo negativo' => [
                ['salario_min' => '-1.00', 'salario_max' => '4000.00'],
                'salario_min',
            ],
            'rango salarial invertido' => [
                ['salario_min' => '4000.00', 'salario_max' => '2000.00'],
                'salario_max',
            ],
            'salario con tres decimales' => [
                ['salario_min' => '2000.001', 'salario_max' => '4000.00'],
                'salario_min',
            ],
            'nivel cero' => [
                ['nivel_puesto_id' => 0],
                'nivel_puesto_id',
            ],
            'nivel como lista' => [
                ['nivel_puesto_id' => [1]],
                'nivel_puesto_id',
            ],
            'area cero' => [
                ['area_estudio_id' => 0],
                'area_estudio_id',
            ],
            'area como lista' => [
                ['area_estudio_id' => [1]],
                'area_estudio_id',
            ],
            'sin habilidades' => [
                ['habilidades' => []],
                'habilidades',
            ],
            'habilidades como texto' => [
                ['habilidades' => 'Python'],
                'habilidades',
            ],
            'requisito desconocido' => [
                [
                    'habilidades' => [
                        [
                            'habilidad_id' => 1,
                            'requisito' => 'opcional',
                        ],
                    ],
                ],
                'habilidades.0.requisito',
            ],
            'cero contrataciones' => [
                ['max_contrataciones' => 0],
                'max_contrataciones',
            ],
            'contrataciones decimales' => [
                ['max_contrataciones' => 1.5],
                'max_contrataciones',
            ],
            'fecha inicial con formato invalido' => [
                ['fecha_inicio_vigencia' => '24/10/2026'],
                'fecha_inicio_vigencia',
            ],
            'fecha final anterior al inicio' => [
                ['fecha_fin_vigencia' => '2000-01-01'],
                'fecha_fin_vigencia',
            ],
        ];
    }

    #[DataProvider('catalogosDeshabilitados')]
    public function test_rechaza_selecciones_desactivadas(
        string $tabla,
        string $propiedad,
        string $columnaEstado,
        mixed $valorEstado,
        string $campo
    ): void {
        DB::table($tabla)
            ->where('id', $this->{$propiedad})
            ->update([$columnaEstado => $valorEstado]);

        $this->comprobarRegistroRechazado(
            $this->datosValidos(),
            $campo
        );
    }

    public static function catalogosDeshabilitados(): array
    {
        return [
            'nivel deshabilitado' => [
                'niveles_puesto',
                'nivelId',
                'estado',
                'inactivo',
                'nivel_puesto_id',
            ],
            'area desactivada' => [
                'areas_estudio',
                'areaId',
                'activo',
                false,
                'area_estudio_id',
            ],
            'habilidad desactivada' => [
                'habilidades',
                'habilidadId',
                'activo',
                false,
                'habilidades.0.habilidad_id',
            ],
        ];
    }

    public function test_registro_aprobacion_y_pago_conservan_los_campos_nuevos(): void
    {
        $this->prepararToken($this->token);

        $registro = $this->postJson(self::RUTA, $this->datosValidos());

        $registro
            ->assertCreated()
            ->assertJsonPath('data.estado.general', 'pendiente_revision');

        $id = $registro->json('data.id');

        $this->assertIsInt($id);

        $datosPago = [
            'titular' => 'Empresa de prueba',
            'numero_tarjeta' => '4111111111111111',
            'vencimiento' => now()->addYears(2)->format('m/y'),
            'cvv' => '123',
        ];

        $this->prepararToken($this->token);

        $this->postJson(self::RUTA.'/'.$id.'/pago', $datosPago)
            ->assertConflict()
            ->assertJsonPath('error_code', 'VACANCY_NOT_APPROVED');

        $this->assertDatabaseMissing('pagos_vacante', [
            'empleo_id' => $id,
        ]);

        $admin = $this->crearUsuario('admin');
        $tokenAdmin = $admin->createToken('prueba-admin')->plainTextToken;

        $this->prepararToken($tokenAdmin);

        $this->getJson('/api/v1/admin/vacantes/'.$id)
            ->assertOk()
            ->assertJsonPath('data.nivel_puesto.id', $this->nivelId)
            ->assertJsonPath('data.area_profesional.id', $this->areaId)
            ->assertJsonPath('data.tipo_empleo', 'remoto')
            ->assertJsonPath('data.ubicacion', null)
            ->assertJsonPath('data.salario_min', null)
            ->assertJsonPath('data.salario_max', null)
            ->assertJsonPath('data.revision.estado', 'pendiente');

        $this->prepararToken($tokenAdmin);

        $this->patchJson('/api/v1/admin/vacantes/'.$id.'/aprobar')
            ->assertOk()
            ->assertJsonPath('data.revision.estado', 'aprobada')
            ->assertJsonPath('data.nivel_puesto.id', $this->nivelId)
            ->assertJsonPath('data.area_profesional.id', $this->areaId)
            ->assertJsonPath('data.salario_min', null)
            ->assertJsonPath('data.salario_max', null);

        $this->assertDatabaseHas('revisiones_vacante', [
            'empleo_id' => $id,
            'admin_id' => $admin->id,
            'decision' => 'aprobada',
        ]);

        $this->prepararToken($this->token);

        $this->getJson(self::RUTA.'/'.$id)
            ->assertOk()
            ->assertJsonPath('data.estado.general', 'pendiente_pago')
            ->assertJsonPath('data.estado.pago', 'sin_pago');

        $this->prepararToken($this->token);

        $pago = $this->postJson(
            self::RUTA.'/'.$id.'/pago',
            $datosPago
        );

        $pago
            ->assertCreated()
            ->assertJsonPath('data.vacante.id', $id)
            ->assertJsonPath(
                'data.vacante.nivel_puesto.id',
                $this->nivelId
            )
            ->assertJsonPath(
                'data.vacante.area_profesional.id',
                $this->areaId
            )
            ->assertJsonPath('data.vacante.tipo_empleo', 'remoto')
            ->assertJsonPath('data.vacante.ubicacion', null)
            ->assertJsonPath('data.vacante.salario_min', null)
            ->assertJsonPath('data.vacante.salario_max', null)
            ->assertJsonPath('data.vacante.estado.revision', 'aprobada')
            ->assertJsonPath('data.vacante.estado.pago', 'pagado')
            ->assertJsonPath('data.vacante.estado.general', 'publicada');

        $this->assertNotNull(
            $pago->json('data.vacante.estado.publicada_en')
        );

        $this->assertDatabaseHas('pagos_vacante', [
            'empleo_id' => $id,
            'resultado' => 'exitoso',
        ]);

        $this->assertSame(
            1,
            DB::table('pagos_vacante')
                ->where('empleo_id', $id)
                ->count()
        );
    }
}
