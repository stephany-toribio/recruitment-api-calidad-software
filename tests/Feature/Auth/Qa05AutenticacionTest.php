<?php

namespace Tests\Feature\Auth;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class Qa05AutenticacionTest extends TestCase
{
    // Credencial ficticia exclusiva de la base SQLite en memoria.
    private const PASSWORD = 'Qa05SoloMemoria123!';

    protected function setUp(): void
    {
        parent::setUp();

        // Estas comprobaciones deben ocurrir ANTES de ejecutar migraciones.
        $this->assertTrue(app()->environment('testing'));
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        foreach ([
            '2026_09_10_020210_create_roles_table.php',
            '2026_09_12_172814_create_usuarios_table.php',
            '2026_09_18_171423_create_personal_access_tokens_table.php',
        ] as $archivo) {
            $migracion = require database_path('migrations/'.$archivo);
            $migracion->up();
        }
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function usuario(
        string $rol = 'candidato',
        string $estado = 'activo'
    ): Usuario {
        $modeloRol = Rol::firstOrCreate(['nombre' => $rol]);

        return Usuario::create([
            'correo' => $rol.'@qa05.example.test',
            'password' => self::PASSWORD,
            'rol_id' => $modeloRol->id,
            'estado_cuenta' => $estado,
        ]);
    }

    private function api(
        string $metodo,
        string $ruta,
        array $datos = [],
        ?string $token = null
    ) {
        $this->app->make('auth')->forgetGuards();

        $headers = $token === null
            ? []
            : ['Authorization' => 'Bearer '.$token];

        return $this->json($metodo, $ruta, $datos, $headers);
    }

    private function login(Usuario $usuario, ?string $password = null)
    {
        return $this->api('POST', '/api/v1/auth/login', [
            'correo' => $usuario->correo,
            'password' => $password ?? self::PASSWORD,
        ]);
    }

    public static function roles(): array
    {
        return [
            'CP-03-01 candidato' => ['candidato'],
            'CP-03-02 empresa' => ['empresa'],
            'CP-03-03 administrador' => ['admin'],
        ];
    }

    #[DataProvider('roles')]
    public function test_login_emite_token_real_e_identidad_del_servidor(
        string $rol
    ): void {
        $usuario = $this->usuario($rol);

        $respuesta = $this->login($usuario)
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.usuario_id', $usuario->id)
            ->assertJsonPath('data.rol', $rol)
            ->assertJsonMissingPath('data.password');

        $token = $respuesta->json('data.token');
        $this->assertIsString($token);

        $registro = PersonalAccessToken::findToken($token);
        $this->assertNotNull($registro);
        $this->assertSame($usuario->id, $registro->tokenable_id);
        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->api('GET', '/api/v1/auth/me', [], $token)
            ->assertOk()
            ->assertJsonPath('data.id', $usuario->id)
            ->assertJsonPath('data.correo', $usuario->correo)
            ->assertJsonPath('data.rol', $rol)
            ->assertJsonMissingPath('data.password');
    }

    public static function entradasInvalidas(): array
    {
        return [
            'campos ausentes' => [[], 'correo'],
            'correo invalido' => [
                ['correo' => 'correo-invalido', 'password' => self::PASSWORD],
                'correo',
            ],
            'password ausente' => [
                ['correo' => 'candidato@qa05.example.test'],
                'password',
            ],
            'password no es texto' => [
                ['correo' => 'candidato@qa05.example.test', 'password' => []],
                'password',
            ],
        ];
    }

    #[DataProvider('entradasInvalidas')]
    public function test_entrada_invalida_no_emite_token_ni_suma_intentos(
        array $datos,
        string $campo
    ): void {
        $usuario = $this->usuario();

        $this->api('POST', '/api/v1/auth/login', $datos)
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors($campo);

        $this->assertSame(0, (int) $usuario->fresh()->intentos_fallidos);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_correo_inexistente_no_emite_token(): void
    {
        $this->api('POST', '/api/v1/auth/login', [
            'correo' => 'inexistente@qa05.example.test',
            'password' => self::PASSWORD,
        ])
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Credenciales incorrectas.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_cp04_01_password_incorrecto_suma_un_intento(): void
    {
        $usuario = $this->usuario();

        $this->login($usuario, 'IncorrectaSoloPrueba!')
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Credenciales incorrectas.')
            ->assertJsonPath('errors.intentos_fallidos', 1)
            ->assertJsonPath('errors.intentos_restantes', 4);

        $actual = $usuario->fresh();
        $this->assertSame(1, (int) $actual->intentos_fallidos);
        $this->assertNull($actual->bloqueado_hasta);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_cp04_02_quinto_fallo_bloquea_por_quince_minutos(): void
    {
        $this->travelTo(now()->startOfSecond());
        $usuario = $this->usuario();

        for ($intento = 1; $intento <= 4; $intento++) {
            $this->login($usuario, 'IncorrectaSoloPrueba!')
                ->assertUnauthorized()
                ->assertJsonPath('errors.intentos_fallidos', $intento)
                ->assertJsonPath('errors.intentos_restantes', 5 - $intento);
        }

        $this->login($usuario, 'IncorrectaSoloPrueba!')
            ->assertStatus(429)
            ->assertJsonPath('errors.intentos_fallidos', 5)
            ->assertJsonPath('errors.intentos_restantes', 0);

        $bloqueado = $usuario->fresh();
        $this->assertTrue(
            $bloqueado->bloqueado_hasta->equalTo(now()->addMinutes(15))
        );

        $antes = $bloqueado->getAttributes();
        $this->login($usuario)->assertStatus(429);
        $this->assertSame($antes, $usuario->fresh()->getAttributes());
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_se_recupera_despues_del_bloqueo(): void
    {
        $this->travelTo(now()->startOfSecond());
        $usuario = $this->usuario();

        $usuario->update([
            'intentos_fallidos' => 5,
            'bloqueado_hasta' => now()->addMinutes(15),
        ]);

        $this->login($usuario)->assertStatus(429);
        $this->travel(16)->minutes();
        $this->login($usuario)->assertOk();

        $actual = $usuario->fresh();
        $this->assertSame(0, (int) $actual->intentos_fallidos);
        $this->assertNull($actual->bloqueado_hasta);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_correcto_reinicia_intentos_previos(): void
    {
        $usuario = $this->usuario();
        $usuario->update(['intentos_fallidos' => 3]);

        $this->login($usuario)->assertOk();

        $this->assertSame(0, (int) $usuario->fresh()->intentos_fallidos);
        $this->assertNull($usuario->fresh()->bloqueado_hasta);
    }

    public static function rolesSuspendibles(): array
    {
        return [
            'candidato suspendido' => ['candidato'],
            'empresa suspendida' => ['empresa'],
        ];
    }

    #[DataProvider('rolesSuspendibles')]
    public function test_cp04_03_cuenta_suspendida_no_obtiene_token(
        string $rol
    ): void {
        $usuario = $this->usuario($rol, 'suspendido');

        $this->login($usuario)
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'message',
                'Su cuenta se encuentra suspendida. Comuníquese con el administrador.'
            );

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_identidad_sin_token_responde_401(): void
    {
        $this->api('GET', '/api/v1/auth/me')
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');
    }

    public function test_cp05_03_token_falso_responde_401(): void
    {
        $this->api('GET', '/api/v1/auth/me', [], 'token_falso_invalido')
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');
    }

    public function test_cp05_01_y_02_logout_revoca_solo_token_actual(): void
    {
        $usuario = $this->usuario();
        $tokenActual = $this->login($usuario)->assertOk()->json('data.token');
        $tokenOtraSesion = $this->login($usuario)->assertOk()->json('data.token');

        $this->assertDatabaseCount('personal_access_tokens', 2);

        $this->api('POST', '/api/v1/auth/logout', [], $tokenActual)
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertNull(PersonalAccessToken::findToken($tokenActual));
        $this->assertNotNull(PersonalAccessToken::findToken($tokenOtraSesion));

        $this->api('GET', '/api/v1/auth/me', [], $tokenActual)
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');

        $this->api('GET', '/api/v1/auth/me', [], $tokenOtraSesion)
            ->assertOk()
            ->assertJsonPath('data.id', $usuario->id);
    }

    public function test_rol_enviado_por_cliente_no_eleva_permisos(): void
    {
        $usuario = $this->usuario();

        $respuesta = $this->api('POST', '/api/v1/auth/login', [
            'correo' => $usuario->correo,
            'password' => self::PASSWORD,
            'rol' => 'admin',
            'rol_id' => 999,
        ])->assertOk()->assertJsonPath('data.rol', 'candidato');

        $this->api(
            'GET',
            '/api/v1/admin/usuarios',
            [],
            $respuesta->json('data.token')
        )
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');

        $this->assertSame('candidato', $usuario->fresh()->rol->nombre);
    }
}
