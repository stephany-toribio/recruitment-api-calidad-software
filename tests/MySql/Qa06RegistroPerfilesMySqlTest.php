<?php

namespace Tests\MySql;

use App\Models\Usuario;
use App\Models\Candidato;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class Qa06RegistroPerfilesMySqlTest extends TestCase
{
    private bool $transaccionIniciada = false;

    private const CANDIDATOS = '/api/v1/candidatos';
    private const EMPRESAS = '/api/v1/empresas';

    // Contraseña exclusivamente sintética para esta suite.
    private const PASSWORD = 'Qa06Integracion#2026';

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertFalse(
            $this->app->configurationIsCached()
        );

        $this->assertTrue(
            $this->app->environment('testing')
        );

        $this->assertSame(
            'mysql',
            DB::connection()->getDriverName()
        );

        $this->assertSame(
            'mysql',
            DB::connection()->getConfig('host')
        );

        $this->assertSame(
            'portalempleoshu04testing',
            DB::selectOne('SELECT DATABASE() AS nombre')->nombre
        );

        DB::beginTransaction();

        $this->transaccionIniciada = true;

        try {
            $this->prepararCatalogos();
        } catch (\Throwable $error) {
            DB::rollBack();
            $this->transaccionIniciada = false;
            throw $error;
        }
    }

    private function prepararCatalogos(): void
    {
        $this->seed([
            \Database\Seeders\RolSeeder::class,
            \Database\Seeders\TipoEstudioSeeder::class,
            \Database\Seeders\AreaEstudioSeeder::class,
            \Database\Seeders\IdiomaSeeder::class,
            \Database\Seeders\HabilidadSeeder::class,
        ]);

        $this->assertDatabaseHas('roles', [
            'nombre' => 'candidato',
        ]);

        $this->assertDatabaseHas('roles', [
            'nombre' => 'empresa',
        ]);

        $this->assertDatabaseHas('tipos_estudio', [
            'nombre' => 'Sin estudios formales',
        ]);
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

    private function correoUnico(): string
    {
        return 'qa06-'.bin2hex(random_bytes(12))
            .'@example.test';
    }

    private function numeroUnico(
        string $tabla,
        string $campo,
        string $prefijo,
        int $longitud
    ): string {
        $restantes = $longitud - strlen($prefijo);

        do {
            $numero = $prefijo.str_pad(
                (string) random_int(0, 10 ** $restantes - 1),
                $restantes,
                '0',
                STR_PAD_LEFT
            );
        } while (
            DB::table($tabla)
                ->where($campo, $numero)
                ->exists()
        );

        return $numero;
    }

    private function datosEmpresa(): array
    {
        return [
            'correo' => $this->correoUnico(),
            'password' => self::PASSWORD,
            'encargado_empresa' => 'Responsable ficticio QA06',
            'razon_social' => 'Empresa ficticia QA06',
            'sector' => 'Tecnología e Informática',
            'ruc' => $this->numeroUnico(
                'empresas', 'ruc', '20', 11
            ),
            'sitio_web' => 'https://empresa.example.test',
            'descripcion' => 'Datos sintéticos de integración.',
        ];
    }

    private function datosCandidato(): array
    {
        $tipo = DB::table('tipos_estudio')
            ->where('nombre', 'Sin estudios formales')
            ->value('id');

        $area = DB::table('areas_estudio')
            ->where('activo', true)
            ->value('id');

        $idioma = DB::table('idiomas')
            ->where('activo', true)
            ->value('id');

        $habilidad = DB::table('habilidades')
            ->where('activo', true)
            ->where('tipo_habilidad', 'blanda')
            ->value('id');

        $this->assertNotNull($tipo, 'Falta tipo de estudio');
        $this->assertNotNull($area, 'Falta área activa');
        $this->assertNotNull($idioma, 'Falta idioma activo');
        $this->assertNotNull($habilidad, 'Falta habilidad blanda');

        return [
            'nombre' => 'Candidato',
            'apellidos' => 'Ficticio QA06',
            'dni' => $this->numeroUnico(
                'candidatos', 'dni', '0', 8
            ),
            'fecha_nacimiento' => '2000-01-01',
            'genero' => 'prefiero no decir',
            'correo' => $this->correoUnico(),
            'password' => self::PASSWORD,
            'password_confirmation' => self::PASSWORD,
            'celular' => $this->numeroUnico(
                'candidatos', 'celular', '9', 9
            ),
            'busca_primer_empleo' => true,
            'estudios' => [
                [
                    'tipo_estudio_id' => (int) $tipo,
                    'area_estudio_id' => (int) $area,
                ],
            ],
            'idiomas' => [
                [
                    'idioma_id' => (int) $idioma,
                    'nivel_escrito' => 'nativo',
                    'nivel_oral' => 'nativo',
                ],
            ],
            'habilidades' => [(int) $habilidad],
        ];
    }

    private function cantidades(): array
    {
        return [
            'usuarios' => DB::table('usuarios')->count(),
            'candidatos' => DB::table('candidatos')->count(),
            'empresas' => DB::table('empresas')->count(),
            'candidato_estudios' =>
                DB::table('candidato_estudios')->count(),
            'candidato_idiomas' =>
                DB::table('candidato_idiomas')->count(),
            'candidato_habilidades' =>
                DB::table('candidato_habilidades')->count(),
        ];
    }

    public function test_cp01_01_registro_candidato_valido(): void
    {
        $datos = $this->datosCandidato();

        $respuesta = $this->postJson(
            self::CANDIDATOS,
            $datos
        )
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.correo', $datos['correo'])
            ->assertJsonPath('data.dni', $datos['dni'])
            ->assertJsonPath('data.celular', $datos['celular'])
            ->assertJsonPath('data.busca_primer_empleo', true)
            ->assertJsonCount(1, 'data.estudios')
            ->assertJsonCount(1, 'data.idiomas')
            ->assertJsonCount(1, 'data.habilidades')
            ->assertJsonCount(0, 'data.experiencias')
            ->assertJsonMissingPath('data.password');

        $usuario = Usuario::where(
            'correo', $datos['correo']
        )->firstOrFail();

        $this->assertSame(
            'candidato',
            $usuario->rol->nombre
        );

        $this->assertNotSame(
            self::PASSWORD,
            $usuario->password
        );

        $this->assertTrue(
            Hash::check(self::PASSWORD, $usuario->password)
        );

        $candidato = Candidato::where(
            'usuario_id', $usuario->id
        )->firstOrFail();

        $this->assertEquals(
            $candidato->id,
            $respuesta->json('data.id')
        );

        $this->assertSame(
            $datos['dni'],
            $candidato->dni
        );

        $this->assertSame(1, $candidato->estudios()->count());
        $this->assertSame(1, $candidato->idiomas()->count());
        $this->assertSame(1, $candidato->habilidades()->count());
        $this->assertSame(0, $candidato->experiencias()->count());
    }

    public static function duplicadosCandidato(): array
    {
        return [
            'correo' => ['correo'],
            'dni' => ['dni'],
            'celular' => ['celular'],
        ];
    }

    #[DataProvider('duplicadosCandidato')]
    public function test_cp01_02_duplicados_candidato(
        string $campo
    ): void {
        $primero = $this->datosCandidato();

        $this->postJson(self::CANDIDATOS, $primero)
            ->assertCreated();

        $segundo = $this->datosCandidato();
        $segundo[$campo] = $primero[$campo];

        $antes = $this->cantidades();

        $this->postJson(self::CANDIDATOS, $segundo)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($campo);

        $this->assertSame($antes, $this->cantidades());
    }

    public static function datosInvalidosCandidato(): array
    {
        return [
            'dni alfanumerico' => ['dni', 'ABC12345'],
            'dni corto' => ['dni', '123'],
            'celular corto' => ['celular', '123'],
            'correo invalido' => ['correo', 'invalido@'],
            'nombre vacio' => ['nombre', ''],
            'apellidos vacios' => ['apellidos', ''],
            'edad insuficiente' => ['fecha_nacimiento', '2020-01-01'],
            'genero invalido' => ['genero', 'inexistente'],
            'password insegura' => ['password', 'abc'],
            'sin estudios' => ['estudios', []],
            'sin idiomas' => ['idiomas', []],
            'sin habilidades' => ['habilidades', []],
            'sin experiencia requerida' => ['busca_primer_empleo', false],
        ];
    }

    #[DataProvider('datosInvalidosCandidato')]
    public function test_cp01_03_datos_invalidos(
        string $campo,
        mixed $valor
    ): void {
        $datos = $this->datosCandidato();
        $datos[$campo] = $valor;

        if ($campo === 'password') {
            $datos['password_confirmation'] = $valor;
        }

        $campoError = $campo === 'busca_primer_empleo'
            ? 'experiencias'
            : $campo;

        $antes = $this->cantidades();

        $this->postJson(self::CANDIDATOS, $datos)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($campoError);

        $this->assertSame($antes, $this->cantidades());
    }

    public function test_confirmacion_password_no_coincide(): void
    {
        $datos = $this->datosCandidato();
        $datos['password_confirmation'] = 'OtraPassword#2026';

        $antes = $this->cantidades();

        $this->postJson(self::CANDIDATOS, $datos)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('password');

        $this->assertSame($antes, $this->cantidades());
    }

    public function test_catalogo_idiomas_no_admite_duplicados(): void
    {
        $datos = $this->datosCandidato();
        $datos['idiomas'][] = $datos['idiomas'][0];

        $antes = $this->cantidades();

        $this->postJson(self::CANDIDATOS, $datos)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('idiomas.0.idioma_id');

        $this->assertSame($antes, $this->cantidades());
    }

    public function test_catalogo_habilidades_no_admite_duplicados(): void
    {
        $datos = $this->datosCandidato();
        $datos['habilidades'][] = $datos['habilidades'][0];

        $antes = $this->cantidades();

        $this->postJson(self::CANDIDATOS, $datos)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('habilidades.0');

        $this->assertSame($antes, $this->cantidades());
    }

    public function test_cp02_01_registro_empresa_valido(): void
    {
        $datos = $this->datosEmpresa();

        $respuesta = $this->postJson(
            self::EMPRESAS,
            $datos
        )
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.correo', $datos['correo'])
            ->assertJsonPath('data.ruc', $datos['ruc'])
            ->assertJsonPath(
                'data.razon_social',
                $datos['razon_social']
            )
            ->assertJsonMissingPath('data.password');

        $usuario = Usuario::where(
            'correo', $datos['correo']
        )->firstOrFail();

        $this->assertSame(
            'empresa',
            $usuario->rol->nombre
        );

        $this->assertNotSame(
            self::PASSWORD,
            $usuario->password
        );

        $this->assertTrue(
            Hash::check(self::PASSWORD, $usuario->password)
        );

        $this->assertDatabaseHas('empresas', [
            'id' => $respuesta->json('data.id'),
            'usuario_id' => $usuario->id,
            'ruc' => $datos['ruc'],
            'razon_social' => $datos['razon_social'],
        ]);
    }

    public static function duplicadosEmpresa(): array
    {
        return [
            'correo' => ['correo'],
            'ruc' => ['ruc'],
        ];
    }

    #[DataProvider('duplicadosEmpresa')]
    public function test_cp02_02_duplicados_empresa(
        string $campo
    ): void {
        $primero = $this->datosEmpresa();

        $this->postJson(self::EMPRESAS, $primero)
            ->assertCreated();

        $segundo = $this->datosEmpresa();
        $segundo[$campo] = $primero[$campo];

        $antes = $this->cantidades();

        $this->postJson(self::EMPRESAS, $segundo)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($campo);

        $this->assertSame($antes, $this->cantidades());
    }

    public static function datosInvalidosEmpresa(): array
    {
        return [
            'ruc corto' => ['ruc', '12345'],
            'ruc alfanumerico' => ['ruc', '2000000000A'],
            'correo invalido' => ['correo', 'invalido@'],
            'sector inexistente' => ['sector', 'No permitido'],
            'encargado vacio' => ['encargado_empresa', ''],
            'razon social vacia' => ['razon_social', ''],
            'url invalida' => ['sitio_web', 'no-es-url'],
            'descripcion extensa' => [
                'descripcion', str_repeat('A', 251)
            ],
            'password insegura' => ['password', 'abc'],
        ];
    }

    #[DataProvider('datosInvalidosEmpresa')]
    public function test_cp02_03_datos_invalidos(
        string $campo,
        mixed $valor
    ): void {
        $datos = $this->datosEmpresa();
        $datos[$campo] = $valor;

        $antes = $this->cantidades();

        $this->postJson(self::EMPRESAS, $datos)
            ->assertUnprocessable()
            ->assertJsonValidationErrors($campo);

        $this->assertSame($antes, $this->cantidades());
    }

    public function test_precognition_candidato_no_persiste(): void
    {
        $datos = $this->datosCandidato();

        $antes = $this->cantidades();

        $this->postJson(
            self::CANDIDATOS,
            [
                'nombre' => $datos['nombre'],
                'dni' => $datos['dni'],
            ],
            [
                'Precognition' => 'true',
                'Precognition-Validate-Only' => 'nombre,dni',
            ]
        )
            ->assertNoContent()
            ->assertHeader('Precognition-Success', 'true');

        $this->assertSame($antes, $this->cantidades());
    }

    public function test_precognition_candidato_invalido(): void
    {
        $antes = $this->cantidades();

        $this->postJson(
            self::CANDIDATOS,
            ['dni' => 'ABC12345'],
            [
                'Precognition' => 'true',
                'Precognition-Validate-Only' => 'dni',
            ]
        )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('dni');

        $this->assertSame($antes, $this->cantidades());
    }

    public function test_precognition_empresa_no_persiste(): void
    {
        $datos = $this->datosEmpresa();

        $antes = $this->cantidades();

        $this->postJson(
            self::EMPRESAS,
            ['ruc' => $datos['ruc']],
            [
                'Precognition' => 'true',
                'Precognition-Validate-Only' => 'ruc',
            ]
        )
            ->assertNoContent()
            ->assertHeader('Precognition-Success', 'true');

        $this->assertSame($antes, $this->cantidades());
    }

    private function autenticar(Usuario $usuario): void
    {
        $this->app['auth']->forgetGuards();
        $this->flushHeaders();

        $this->withToken(
            $usuario->createToken('qa06-integracion')->plainTextToken
        );
    }

    private function registrarEmpresaQa06(): array
    {
        $datos = $this->datosEmpresa();

        $respuesta = $this->postJson(
            self::EMPRESAS, $datos
        )->assertCreated();

        return [
            $datos,
            Usuario::where('correo', $datos['correo'])->firstOrFail(),
            $respuesta->json('data.id'),
        ];
    }

    private function registrarCandidatoQa06(): array
    {
        $datos = $this->datosCandidato();

        $respuesta = $this->postJson(
            self::CANDIDATOS, $datos
        )->assertCreated();

        return [
            $datos,
            Usuario::where('correo', $datos['correo'])->firstOrFail(),
            $respuesta->json('data.id'),
        ];
    }

    private function payloadEmpresaPerfil(array $datos): array
    {
        unset($datos['correo'], $datos['password']);

        return $datos;
    }

    public function test_empresa_consulta_perfil_propio(): void
    {
        [$datos, $usuario, $id] = $this->registrarEmpresaQa06();

        $this->autenticar($usuario);

        $this->getJson(self::EMPRESAS.'/me')
            ->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.correo', $datos['correo'])
            ->assertJsonPath('data.ruc', $datos['ruc'])
            ->assertJsonMissingPath('data.password');
    }

    public function test_empresa_actualiza_y_persiste_perfil(): void
    {
        [$datos, $usuario, $id] = $this->registrarEmpresaQa06();

        $payload = $this->payloadEmpresaPerfil($datos);
        $payload['razon_social'] = 'Empresa actualizada QA06';
        $payload['descripcion'] = 'Descripcion actualizada';

        $this->autenticar($usuario);

        $this->putJson(self::EMPRESAS.'/me', $payload)
            ->assertOk()
            ->assertJsonPath('data.razon_social', $payload['razon_social'])
            ->assertJsonPath('data.descripcion', $payload['descripcion']);

        $this->assertDatabaseHas('empresas', [
            'id' => $id,
            'razon_social' => $payload['razon_social'],
            'descripcion' => $payload['descripcion'],
        ]);

        $this->getJson(self::EMPRESAS.'/me')
            ->assertOk()
            ->assertJsonPath('data.razon_social', $payload['razon_social'])
            ->assertJsonPath('data.descripcion', $payload['descripcion']);
    }

    public function test_empresa_no_utiliza_ruc_ajeno(): void
    {
        [$datos, $usuario, $id] = $this->registrarEmpresaQa06();
        [$otra] = $this->registrarEmpresaQa06();

        $payload = $this->payloadEmpresaPerfil($datos);
        $payload['ruc'] = $otra['ruc'];

        $antes = DB::table('empresas')->where('id', $id)->first();

        $this->autenticar($usuario);

        $this->putJson(self::EMPRESAS.'/me', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('ruc');

        $this->assertEquals(
            $antes,
            DB::table('empresas')->where('id', $id)->first()
        );
    }

    public function test_empresa_ignora_identificadores_ajenos(): void
    {
        [$datos, $usuario, $id] = $this->registrarEmpresaQa06();
        [$otra, $otroUsuario, $otroId] = $this->registrarEmpresaQa06();

        $antes = DB::table('empresas')
            ->where('id', $otroId)->first();

        $payload = $this->payloadEmpresaPerfil($datos);

        $payload['id'] = $otroId;
        $payload['empresa_id'] = $otroId;
        $payload['usuario_id'] = $otroUsuario->id;
        $payload['rol'] = 'admin';
        $payload['correo'] = 'ignorado@example.test';
        $payload['razon_social'] = 'Perfil propio actualizado';

        $this->autenticar($usuario);

        $this->putJson(self::EMPRESAS.'/me', $payload)
            ->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.correo', $datos['correo'])
            ->assertJsonPath(
                'data.razon_social',
                'Perfil propio actualizado'
            );

        $this->assertEquals(
            $antes,
            DB::table('empresas')->where('id', $otroId)->first()
        );

        $this->assertSame(
            'empresa',
            $usuario->fresh()->rol->nombre
        );
    }

    public function test_perfiles_rechazan_roles_incompatibles(): void
    {
        [$datosEmpresa, $empresa] = $this->registrarEmpresaQa06();
        [$datosCandidato, $candidato] = $this->registrarCandidatoQa06();

        $this->autenticar($empresa);

        $this->getJson(self::CANDIDATOS.'/me')
            ->assertForbidden();

        $this->putJson(self::CANDIDATOS.'/me', [
            'nombre' => 'Cambio no permitido',
        ])->assertForbidden();

        $this->autenticar($candidato);

        $this->getJson(self::EMPRESAS.'/me')
            ->assertForbidden();

        $this->putJson(
            self::EMPRESAS.'/me',
            $this->payloadEmpresaPerfil($datosEmpresa)
        )->assertForbidden();
    }

    public function test_perfiles_requieren_autenticacion(): void
    {
        $this->getJson(self::CANDIDATOS.'/me')
            ->assertUnauthorized();

        $this->getJson(self::EMPRESAS.'/me')
            ->assertUnauthorized();

        $this->putJson(self::CANDIDATOS.'/me', [])
            ->assertUnauthorized();

        $this->putJson(self::EMPRESAS.'/me', [])
            ->assertUnauthorized();
    }

    public function test_candidato_registrado_consulta_perfil_completo(): void
    {
        [$datos, $usuario, $id] = $this->registrarCandidatoQa06();

        $this->autenticar($usuario);

        $this->getJson(self::CANDIDATOS.'/me')
            ->assertOk()
            ->assertJsonPath('data.id', $id)
            ->assertJsonPath('data.correo', $datos['correo'])
            ->assertJsonCount(1, 'data.estudios')
            ->assertJsonCount(1, 'data.idiomas')
            ->assertJsonCount(1, 'data.habilidades')
            ->assertJsonMissingPath('data.password');
    }

    public function test_candidato_actualiza_datos_sin_perder_relaciones(): void
    {
        [$datos, $usuario, $id] = $this->registrarCandidatoQa06();

        $celular = $this->numeroUnico(
            'candidatos', 'celular', '9', 9
        );

        $this->autenticar($usuario);

        $this->putJson(self::CANDIDATOS.'/me', [
            'celular' => $celular,
        ])
            ->assertOk()
            ->assertJsonPath('data.celular', $celular)
            ->assertJsonCount(1, 'data.estudios')
            ->assertJsonCount(1, 'data.idiomas')
            ->assertJsonCount(1, 'data.habilidades');

        $this->assertDatabaseHas('candidatos', [
            'id' => $id,
            'celular' => $celular,
        ]);

        $this->getJson(self::CANDIDATOS.'/me')
            ->assertOk()
            ->assertJsonPath('data.celular', $celular);
    }

    public function test_candidato_no_utiliza_dni_ni_celular_ajeno(): void
    {
        [$datos, $usuario, $id] = $this->registrarCandidatoQa06();
        [$otro] = $this->registrarCandidatoQa06();

        $antes = DB::table('candidatos')
            ->where('id', $id)->first();

        $this->autenticar($usuario);

        $this->putJson(self::CANDIDATOS.'/me', [
            'dni' => $otro['dni'],
            'celular' => $otro['celular'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['dni', 'celular']);

        $this->assertEquals(
            $antes,
            DB::table('candidatos')->where('id', $id)->first()
        );
    }

    public function test_correo_es_unico_entre_roles(): void
    {
        [$empresa] = $this->registrarEmpresaQa06();

        $candidato = $this->datosCandidato();
        $candidato['correo'] = $empresa['correo'];

        $antes = $this->cantidades();

        $this->postJson(self::CANDIDATOS, $candidato)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('correo');

        $this->assertSame($antes, $this->cantidades());
    }

    public function test_registro_ignora_rol_administrador_inyectado(): void
    {
        $datos = $this->datosEmpresa();

        $datos['rol'] = 'admin';
        $datos['rol_id'] = DB::table('roles')
            ->where('nombre', 'admin')->value('id');

        $this->postJson(self::EMPRESAS, $datos)
            ->assertCreated();

        $usuario = Usuario::where(
            'correo', $datos['correo']
        )->firstOrFail();

        $this->assertSame('empresa', $usuario->rol->nombre);
    }

    public function test_candidato_rechaza_catalogo_inexistente(): void
    {
        $datos = $this->datosCandidato();
        $datos['idiomas'][0]['idioma_id'] = -1;

        $antes = $this->cantidades();

        $this->postJson(self::CANDIDATOS, $datos)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('idiomas.0.idioma_id');

        $this->assertSame($antes, $this->cantidades());
    }

    public function test_candidato_rechaza_mas_de_30_habilidades(): void
    {
        $datos = $this->datosCandidato();

        $datos['habilidades'] = array_fill(
            0, 31, $datos['habilidades'][0]
        );

        $antes = $this->cantidades();

        $this->postJson(self::CANDIDATOS, $datos)
            ->assertStatus(400)
            ->assertJsonPath(
                'error_code',
                'MAX_HABILIDADES_EXCEDIDO'
            );

        $this->assertSame($antes, $this->cantidades());
    }


    public function test_base_datos_garantiza_unicidad(): void
    {
        $restricciones = [
            ['usuarios', 'correo'],
            ['candidatos', 'dni'],
            ['candidatos', 'celular'],
            ['empresas', 'ruc'],
        ];

        foreach ($restricciones as [$tabla, $campo]) {
            $indices = DB::select(
                'SELECT INDEX_NAME
                 FROM information_schema.STATISTICS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = ?
                   AND NON_UNIQUE = 0
                 GROUP BY INDEX_NAME
                 HAVING COUNT(*) = 1
                    AND MAX(COLUMN_NAME) = ?',
                [$tabla, $campo]
            );

            $this->assertNotEmpty(
                $indices,
                "Falta restriccion UNIQUE para {$tabla}.{$campo}"
            );
        }
    }

}
