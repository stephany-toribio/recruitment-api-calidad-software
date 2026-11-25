<?php

namespace Tests\Feature\Auth;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class Qa05PasswordTest extends TestCase
{
    private const PASSWORD = 'Qa05SoloMemoria123!';
    private const NUEVA = 'NuevaClaveQa05#2026';
    private const RUTA = '/api/v1/auth/usuarios/me/password';

    protected function setUp(): void
    {
        parent::setUp();
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

    private function usuario(string $rol): Usuario
    {
        $modelo = Rol::firstOrCreate(['nombre' => $rol]);

        return Usuario::create([
            'correo' => $rol.'@qa05.example.test',
            'password' => self::PASSWORD,
            'rol_id' => $modelo->id,
            'estado_cuenta' => 'activo',
        ]);
    }

    private function api(string $metodo, string $ruta, array $datos = [], ?string $token = null)
    {
        $this->app->make('auth')->forgetGuards();

        return $this->json($metodo, $ruta, $datos, $token === null
            ? [] : ['Authorization' => 'Bearer '.$token]);
    }

    private function login(Usuario $usuario, string $password = self::PASSWORD)
    {
        return $this->api('POST', '/api/v1/auth/login', [
            'correo' => $usuario->correo,
            'password' => $password,
        ]);
    }

    private function token(Usuario $usuario): string
    {
        $token = $this->login($usuario)->assertOk()->json('data.token');
        $this->assertIsString($token);

        return $token;
    }

    private function datos(): array
    {
        return [
            'password_actual' => self::PASSWORD,
            'password_nuevo' => self::NUEVA,
            'password_nuevo_confirmation' => self::NUEVA,
        ];
    }

    public static function roles(): array
    {
        return ['candidato' => ['candidato'], 'empresa' => ['empresa'], 'admin' => ['admin']];
    }

    #[DataProvider('roles')]
    public function test_cambio_valido_revoca_todas_las_sesiones_solo_del_usuario(string $rol): void
    {
        $usuario = $this->usuario($rol);
        $otro = $this->usuario($rol === 'admin' ? 'empresa' : 'admin');
        $primero = $this->token($usuario);
        $segundo = $this->token($usuario);
        $tokenOtro = $this->token($otro);
        $hashOtro = $otro->fresh()->password;

        $this->api('PUT', self::RUTA, $this->datos(), $primero)
            ->assertOk()->assertJsonPath('success', true)
            ->assertJsonMissingPath('data.password');

        $actual = $usuario->fresh();
        $this->assertTrue(Hash::check(self::NUEVA, $actual->password));
        $this->assertFalse(Hash::check(self::PASSWORD, $actual->password));
        $this->assertSame(0, $actual->tokens()->count());

        foreach ([$primero, $segundo] as $revocado) {
            $this->assertNull(PersonalAccessToken::findToken($revocado));
            $this->api('GET', '/api/v1/auth/me', [], $revocado)
                ->assertUnauthorized()->assertJsonPath('error_code', 'UNAUTHENTICATED');
        }

        $this->assertSame($hashOtro, $otro->fresh()->password);
        $this->api('GET', '/api/v1/auth/me', [], $tokenOtro)
            ->assertOk()->assertJsonPath('data.id', $otro->id);

        $this->login($usuario)->assertUnauthorized();
        $this->login($usuario, self::NUEVA)->assertOk();
    }

    public function test_password_actual_incorrecto_no_modifica_ni_revoca(): void
    {
        $usuario = $this->usuario('candidato');
        $token = $this->token($usuario);
        $hash = $usuario->fresh()->password;
        $datos = $this->datos();
        $datos['password_actual'] = 'IncorrectaQa05#2026';

        $this->api('PUT', self::RUTA, $datos, $token)
            ->assertUnprocessable()->assertJsonValidationErrors('password_actual');

        $this->assertSame($hash, $usuario->fresh()->password);
        $this->assertNotNull(PersonalAccessToken::findToken($token));
        $this->api('GET', '/api/v1/auth/me', [], $token)->assertOk();
    }

    public function test_confirmacion_invalida_no_modifica_ni_revoca(): void
    {
        $usuario = $this->usuario('candidato');
        $token = $this->token($usuario);
        $hash = $usuario->fresh()->password;
        $datos = $this->datos();
        $datos['password_nuevo_confirmation'] = 'OtraClaveQa05#2026';

        $this->api('PUT', self::RUTA, $datos, $token)
            ->assertUnprocessable()->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors('password_nuevo');

        $this->assertSame($hash, $usuario->fresh()->password);
        $this->assertNotNull(PersonalAccessToken::findToken($token));
    }

    public static function rolesSuspendibles(): array
    {
        return ['candidato' => ['candidato'], 'empresa' => ['empresa']];
    }

    #[DataProvider('rolesSuspendibles')]
    public function test_cuenta_suspendida_con_token_existente_no_cambia_password(string $rol): void
    {
        $usuario = $this->usuario($rol);
        $token = $this->token($usuario);
        $hash = $usuario->fresh()->password;

        DB::table('usuarios')->where('id', $usuario->id)->update([
            'estado_cuenta' => 'suspendido',
        ]);

        $this->api('PUT', self::RUTA, $this->datos(), $token)
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN')
            ->assertJsonPath('reason', 'ACCOUNT_NOT_ACTIVE');

        $this->assertSame($hash, $usuario->fresh()->password);
        $this->assertNotNull(PersonalAccessToken::findToken($token));
    }

    public function test_logout_sigue_disponible_para_token_de_cuenta_suspendida(): void
    {
        $usuario = $this->usuario('candidato');
        $token = $this->token($usuario);

        DB::table('usuarios')->where('id', $usuario->id)->update([
            'estado_cuenta' => 'suspendido',
        ]);

        $this->api('POST', '/api/v1/auth/logout', [], $token)
            ->assertOk()->assertJsonPath('success', true);

        $this->assertNull(PersonalAccessToken::findToken($token));
        $this->api('GET', '/api/v1/auth/me', [], $token)->assertUnauthorized();
    }
}
