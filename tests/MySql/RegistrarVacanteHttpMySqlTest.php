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

    /** @var int[] Completan el mínimo de 5 habilidades que exige la vacante */
    private array $habilidadesExtra = [];

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

            foreach (range(1, 4) as $numero) {
                $this->habilidadesExtra[] = DB::table('habilidades')->insertGetId([
                    'nombre' => "Habilidad HTTP {$etiqueta} {$numero}",
                    'tipo_habilidad' => 'blanda',
                    'area_estudio_id' => null,
                    'activo' => true,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }

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
                ...array_map(fn (int $id) => [
                    'habilidad_id' => $id,
                    'requisito' => 'nice_to_have',
                ], $this->habilidadesExtra),
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

    private function registrarVacanteQa07(): int
    {
        $this->prepararToken($this->token);

        $respuesta = $this->postJson(
            self::RUTA,
            $this->datosValidos()
        );

        $respuesta->assertCreated();

        $id = $respuesta->json('data.id');

        $this->assertIsInt($id);

        return $id;
    }

    private function aprobarVacanteQa07(int $id): void
    {
        $admin = $this->crearUsuario('admin');

        $this->prepararToken(
            $admin->createToken('qa07-admin')->plainTextToken
        );

        $this->patchJson(
            '/api/v1/admin/vacantes/'.$id.'/aprobar'
        )
            ->assertOk()
            ->assertJsonPath('data.revision.estado', 'aprobada');
    }

    private function datosPagoQa07(): array
    {
        return [
            'titular' => 'Empresa ficticia QA',
            'numero_tarjeta' => '4111111111111111',
            'vencimiento' => now()->addYears(2)->format('m/y'),
            'cvv' => '123',
        ];
    }

    public function test_qa07_tarjeta_luhn_invalida(): void
    {
        $id = $this->registrarVacanteQa07();

        $this->aprobarVacanteQa07($id);

        $this->prepararToken($this->token);

        $datos = $this->datosPagoQa07();

        $datos['numero_tarjeta'] = '4111111111111112';

        $this->postJson(self::RUTA.'/'.$id.'/pago', $datos)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('numero_tarjeta');

        $this->assertDatabaseMissing('pagos_vacante', [
            'empleo_id' => $id,
        ]);
    }

    public function test_qa07_pago_duplicado_no_genera_otro_registro(): void
    {
        $id = $this->registrarVacanteQa07();

        $this->aprobarVacanteQa07($id);

        $this->prepararToken($this->token);

        $ruta = self::RUTA.'/'.$id.'/pago';

        $this->postJson($ruta, $this->datosPagoQa07())
            ->assertCreated();

        $this->prepararToken($this->token);

        $this->postJson($ruta, $this->datosPagoQa07())
            ->assertConflict()
            ->assertJsonPath(
                'error_code',
                'VACANCY_ALREADY_PAID'
            );

        $this->assertSame(
            1,
            DB::table('pagos_vacante')
                ->where('empleo_id', $id)
                ->count()
        );

        $this->assertDatabaseHas('pagos_vacante', [
            'empleo_id' => $id,
            'resultado' => 'exitoso',
            'tarjeta_ultimos4' => '1111',
        ]);
    }

    public function test_qa07_admin_no_aprueba_con_motivo(): void
    {
        $id = $this->registrarVacanteQa07();

        $admin = $this->crearUsuario('admin');

        $this->prepararToken(
            $admin->createToken('qa07-admin')->plainTextToken
        );

        $motivo = 'Descripcion insuficiente del puesto';

        $this->patchJson(
            '/api/v1/admin/vacantes/'.$id.'/no-aprobar',
            ['motivo' => $motivo]
        )
            ->assertOk()
            ->assertJsonPath(
                'data.revision.estado',
                'no_aprobada'
            );

        $this->assertDatabaseHas('revisiones_vacante', [
            'empleo_id' => $id,
            'admin_id' => $admin->id,
            'decision' => 'no_aprobada',
            'motivo' => $motivo,
        ]);

        $this->prepararToken($this->token);

        $this->postJson(
            self::RUTA.'/'.$id.'/pago',
            $this->datosPagoQa07()
        )
            ->assertConflict()
            ->assertJsonPath(
                'error_code',
                'VACANCY_NOT_APPROVED'
            );

        $this->assertDatabaseMissing('pagos_vacante', [
            'empleo_id' => $id,
        ]);
    }

    public function test_qa07_no_aprobar_sin_motivo(): void
    {
        $id = $this->registrarVacanteQa07();

        $admin = $this->crearUsuario('admin');

        $token = $admin
            ->createToken('qa07-admin')
            ->plainTextToken;

        foreach ([[], ['motivo' => '   ']] as $datos) {
            $this->prepararToken($token);

            $this->patchJson(
                '/api/v1/admin/vacantes/'.$id.'/no-aprobar',
                $datos
            )
                ->assertUnprocessable()
                ->assertJsonValidationErrors('motivo');

            $this->assertDatabaseMissing('revisiones_vacante', [
                'empleo_id' => $id,
            ]);
        }
    }

    public function test_qa07_empresa_no_puede_aprobar(): void
    {
        $id = $this->registrarVacanteQa07();

        $this->prepararToken($this->token);

        $this->patchJson(
            '/api/v1/admin/vacantes/'.$id.'/aprobar'
        )
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');

        $this->assertDatabaseMissing('revisiones_vacante', [
            'empleo_id' => $id,
        ]);
    }

    public function test_qa07_revision_duplicada_es_rechazada(): void
    {
        $id = $this->registrarVacanteQa07();

        $this->aprobarVacanteQa07($id);

        $admin = $this->crearUsuario('admin');

        $this->prepararToken(
            $admin->createToken('qa07-admin')->plainTextToken
        );

        $this->patchJson(
            '/api/v1/admin/vacantes/'.$id.'/aprobar'
        )
            ->assertConflict()
            ->assertJsonPath(
                'error_code',
                'VACANCY_ALREADY_REVIEWED'
            );

        $this->assertSame(
            1,
            DB::table('revisiones_vacante')
                ->where('empleo_id', $id)
                ->count()
        );
    }

    public function test_qa07_pago_con_datos_invalidos(): void
    {
        $id = $this->registrarVacanteQa07();

        $this->aprobarVacanteQa07($id);

        $casos = [
            ['titular', '   '],
            ['numero_tarjeta', '123'],
            ['vencimiento', '13/99'],
            ['vencimiento', now()->subYears(2)->format('m/y')],
            ['cvv', '12'],
        ];

        foreach ($casos as [$campo, $valor]) {
            $this->prepararToken($this->token);

            $datos = $this->datosPagoQa07();
            $datos[$campo] = $valor;

            $this->postJson(
                self::RUTA.'/'.$id.'/pago',
                $datos
            )
                ->assertUnprocessable()
                ->assertJsonValidationErrors($campo);

            $this->assertDatabaseMissing('pagos_vacante', [
                'empleo_id' => $id,
            ]);
        }
    }


    private function crearOtraEmpresaQa07(): Usuario
    {
        $usuario = $this->crearUsuario('empresa');

        DB::table('empresas')->insert([
            'usuario_id' => $usuario->id,
            'encargado_empresa' => 'Responsable QA',
            'razon_social' => 'Empresa QA '.bin2hex(random_bytes(6)),
            'sector' => 'Tecnologia e Informatica',
            'ruc' => '20'.random_int(100000000, 999999999),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $usuario;
    }

    private function publicarVacanteQa07(): int
    {
        $id = $this->registrarVacanteQa07();

        $this->aprobarVacanteQa07($id);

        $this->prepararToken($this->token);

        $this->postJson(
            self::RUTA.'/'.$id.'/pago',
            $this->datosPagoQa07()
        )->assertCreated();

        return $id;
    }

    public function test_qa07_empresa_ajena_no_puede_pagar(): void
    {
        $id = $this->registrarVacanteQa07();

        $this->aprobarVacanteQa07($id);

        $otraEmpresa = $this->crearOtraEmpresaQa07();

        $token = $otraEmpresa
            ->createToken('qa07-empresa-ajena')
            ->plainTextToken;

        $this->prepararToken($token);

        $this->getJson(self::RUTA.'/'.$id.'/pago')
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');

        $this->prepararToken($token);

        $this->postJson(
            self::RUTA.'/'.$id.'/pago',
            $this->datosPagoQa07()
        )
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');

        $this->assertDatabaseMissing('pagos_vacante', [
            'empleo_id' => $id,
        ]);
    }

    public function test_qa07_monto_calculado_por_servidor(): void
    {
        foreach ([
            ['vacante', 100.00],
            ['puesto', 200.00],
        ] as [$unidad, $esperado]) {

            config([
                'pago_vacantes.tarifa_por_puesto' => 100,
                'pago_vacantes.unidad_cobro' => $unidad,
                'pago_vacantes.moneda' => 'PEN',
            ]);

            $id = $this->registrarVacanteQa07();

            $this->aprobarVacanteQa07($id);

            $this->prepararToken($this->token);

            $resumen = $this->getJson(
                self::RUTA.'/'.$id.'/pago'
            )->assertOk();

            $this->assertEquals(
                $esperado,
                $resumen->json('data.monto')
            );

            $this->prepararToken($this->token);

            $datos = $this->datosPagoQa07();

            // El cliente no debe controlar el monto.
            $datos['monto'] = 1;
            $datos['moneda'] = 'USD';

            $respuesta = $this->postJson(
                self::RUTA.'/'.$id.'/pago',
                $datos
            )->assertCreated();

            $this->assertEquals(
                $esperado,
                $respuesta->json('data.pago.monto')
            );

            $this->assertDatabaseHas('pagos_vacante', [
                'empleo_id' => $id,
                'resultado' => 'exitoso',
                'monto' => $esperado,
                'moneda' => 'PEN',
            ]);
        }
    }

    public function test_qa07_pago_protege_datos_de_tarjeta(): void
    {
        $id = $this->registrarVacanteQa07();

        $this->aprobarVacanteQa07($id);

        $this->prepararToken($this->token);

        $datos = $this->datosPagoQa07();

        $respuesta = $this->postJson(
            self::RUTA.'/'.$id.'/pago',
            $datos
        )
            ->assertCreated()
            ->assertJsonPath('data.pago.tarjeta', '**** 1111');

        $pago = $respuesta->json('data.pago');

        $esperadas = [
            'id',
            'resultado',
            'monto',
            'tarjeta',
            'fecha',
        ];

        $actuales = array_keys($pago);

        sort($esperadas);
        sort($actuales);

        $this->assertSame($esperadas, $actuales);

        $this->assertStringNotContainsString(
            $datos['numero_tarjeta'],
            $respuesta->getContent()
        );

        $registro = (array) DB::table('pagos_vacante')
            ->where('empleo_id', $id)
            ->first();

        $this->assertSame(
            '1111',
            $registro['tarjeta_ultimos4']
        );

        foreach ([
            'numero_tarjeta',
            'cvv',
            'titular',
            'vencimiento',
        ] as $campo) {

            $this->assertArrayNotHasKey($campo, $registro);
            $this->assertArrayNotHasKey($campo, $pago);
        }

        $this->prepararToken($this->token);

        $this->getJson(self::RUTA.'/'.$id.'/pago')
            ->assertOk()
            ->assertJsonPath(
                'data.intentos.0.tarjeta',
                '**** 1111'
            )
            ->assertJsonMissingPath('data.intentos.0.cvv')
            ->assertJsonMissingPath(
                'data.intentos.0.numero_tarjeta'
            );
    }

    public function test_qa07_editar_invalida_aprobacion_vigente(): void
    {
        $id = $this->registrarVacanteQa07();

        $this->aprobarVacanteQa07($id);

        $datos = $this->datosValidos();

        $datos['titulo'] = 'Vacante actualizada QA07';

        $this->prepararToken($this->token);

        $this->putJson(self::RUTA.'/'.$id, $datos)
            ->assertOk()
            ->assertJsonPath(
                'data.estado.general',
                'pendiente_revision'
            );

        $this->assertDatabaseHas('empleos', [
            'id' => $id,
            'titulo' => $datos['titulo'],
        ]);

        $revision = DB::table('revisiones_vacante')
            ->where('empleo_id', $id)
            ->first();

        $this->assertNotNull($revision);
        $this->assertNotNull($revision->invalidada_en);

        $this->prepararToken($this->token);

        $this->postJson(
            self::RUTA.'/'.$id.'/pago',
            $this->datosPagoQa07()
        )
            ->assertConflict()
            ->assertJsonPath(
                'error_code',
                'VACANCY_NOT_APPROVED'
            );

        $this->assertDatabaseMissing('pagos_vacante', [
            'empleo_id' => $id,
        ]);

        // Una nueva aprobacion debe volver a ser posible.
        $this->aprobarVacanteQa07($id);
    }

    public function test_qa07_vacante_pagada_no_se_edita(): void
    {
        $id = $this->publicarVacanteQa07();

        $anterior = (array) DB::table('empleos')
            ->where('id', $id)
            ->first();

        $datos = $this->datosValidos();

        $datos['titulo'] = 'Modificacion no permitida QA';

        $this->prepararToken($this->token);

        $this->putJson(self::RUTA.'/'.$id, $datos)
            ->assertConflict()
            ->assertJsonPath(
                'error_code',
                'VACANCY_ALREADY_PAID'
            );

        $this->assertSame(
            $anterior,
            (array) DB::table('empleos')
                ->where('id', $id)
                ->first()
        );
    }

    public function test_qa07_no_aprobada_no_se_edita(): void
    {
        $id = $this->registrarVacanteQa07();

        $admin = $this->crearUsuario('admin');

        $this->prepararToken(
            $admin->createToken('qa07-admin')->plainTextToken
        );

        $this->patchJson(
            '/api/v1/admin/vacantes/'.$id.'/no-aprobar',
            ['motivo' => 'Descripcion insuficiente']
        )->assertOk();

        $anterior = (array) DB::table('empleos')
            ->where('id', $id)
            ->first();

        $datos = $this->datosValidos();

        $datos['titulo'] = 'Modificacion rechazada QA';

        $this->prepararToken($this->token);

        $this->putJson(self::RUTA.'/'.$id, $datos)
            ->assertConflict()
            ->assertJsonPath(
                'error_code',
                'VACANCY_NOT_EDITABLE'
            );

        $this->assertSame(
            $anterior,
            (array) DB::table('empleos')
                ->where('id', $id)
                ->first()
        );
    }

    public function test_qa07_transiciones_de_vacante_publicada(): void
    {
        $id = $this->publicarVacanteQa07();

        $ruta = self::RUTA.'/'.$id;

        foreach ([
            ['pausar', 'pausada'],
            ['reanudar', 'activa'],
            ['finalizar', 'finalizada'],
        ] as [$accion, $estado]) {

            $this->prepararToken($this->token);

            $this->patchJson($ruta.'/'.$accion)
                ->assertOk();

            $this->assertDatabaseHas('empleos', [
                'id' => $id,
                'estado_empleo' => $estado,
            ]);
        }

        $this->prepararToken($this->token);

        $this->patchJson($ruta.'/reanudar')
            ->assertConflict()
            ->assertJsonPath(
                'error_code',
                'VACANCY_FINALIZED'
            );

        $this->assertDatabaseHas('empleos', [
            'id' => $id,
            'estado_empleo' => 'finalizada',
        ]);
    }

    public function test_qa07_no_pausa_vacante_sin_publicar(): void
    {
        $id = $this->registrarVacanteQa07();

        $this->prepararToken($this->token);

        $this->patchJson(self::RUTA.'/'.$id.'/pausar')
            ->assertConflict()
            ->assertJsonPath(
                'error_code',
                'VACANCY_NOT_PUBLISHED'
            );

        $this->assertDatabaseHas('empleos', [
            'id' => $id,
            'estado_empleo' => 'activa',
        ]);
    }

    public function test_qa07_no_reanuda_vacante_vencida(): void
    {
        $id = $this->publicarVacanteQa07();

        $ruta = self::RUTA.'/'.$id;

        $this->prepararToken($this->token);

        $this->patchJson($ruta.'/pausar')
            ->assertOk();

        DB::table('empleos')
            ->where('id', $id)
            ->update([
                'fecha_inicio_vigencia' =>
                    now('America/Lima')->subDays(7)->toDateString(),
                'fecha_fin_vigencia' =>
                    now('America/Lima')->subDay()->toDateString(),
            ]);

        $this->prepararToken($this->token);

        $this->patchJson($ruta.'/reanudar')
            ->assertConflict()
            ->assertJsonPath(
                'error_code',
                'VACANCY_EXPIRED'
            );

        $this->assertDatabaseHas('empleos', [
            'id' => $id,
            'estado_empleo' => 'pausada',
        ]);
    }

    public function test_qa07_eliminacion_logica_conserva_historial(): void
    {
        $id = $this->publicarVacanteQa07();

        $this->prepararToken($this->token);

        $this->deleteJson(self::RUTA.'/'.$id)
            ->assertOk();

        $registro = DB::table('empleos')
            ->where('id', $id)
            ->first();

        $this->assertNotNull($registro);
        $this->assertNotNull($registro->deleted_at);

        $this->assertDatabaseHas('pagos_vacante', [
            'empleo_id' => $id,
            'resultado' => 'exitoso',
        ]);

        $this->assertDatabaseHas('revisiones_vacante', [
            'empleo_id' => $id,
            'decision' => 'aprobada',
        ]);
    }

}
