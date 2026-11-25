<?php

namespace Tests\Feature\Auth;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\PersonalAccessToken;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class Qa05SuspensionTest extends TestCase
{
    // Credencial ficticia exclusiva de los usuarios creados en memoria.
    private const PASSWORD = 'Qa05SoloMemoria123!';

    private int $secuencia = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertTrue(app()->environment('testing'));
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        foreach ([
            '2026_09_10_020210_create_roles_table.php',
            '2026_09_12_172814_create_usuarios_table.php',
            '2026_09_12_173957_create_empresas_table.php',
            '2026_09_12_174322_create_candidatos_table.php',
            '2026_09_18_171423_create_personal_access_tokens_table.php',
        ] as $archivo) {
            $migracion = require database_path('migrations/'.$archivo);
            $migracion->up();
        }
    }

    private function usuario(string $rol, string $estado = 'activo'): Usuario
    {
        $numero = ++$this->secuencia;
        $modeloRol = Rol::firstOrCreate(['nombre' => $rol]);

        $usuario = Usuario::create([
            'correo' => $rol.$numero.'@qa05.example.test',
            'password' => self::PASSWORD,
            'rol_id' => $modeloRol->id,
            'estado_cuenta' => $estado,
        ]);

        if ($rol === 'candidato') {
            DB::table('candidatos')->insert([
                'usuario_id' => $usuario->id,
                'nombre' => 'Persona QA '.$numero,
                'apellidos' => 'Prueba ficticia',
                'dni' => str_pad((string) $numero, 8, '0', STR_PAD_LEFT),
                'celular' => '900000001',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } elseif ($rol === 'empresa') {
            DB::table('empresas')->insert([
                'usuario_id' => $usuario->id,
                'razon_social' => 'Empresa ficticia QA '.$numero,
                'ruc' => str_pad((string) $numero, 11, '0', STR_PAD_LEFT),
                'sitio_web' => 'https://empresa.example.test',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $usuario;
    }

    private function api(
        string $metodo,
        string $ruta,
        array $datos = [],
        ?string $token = null
    ) {
        $this->app->make('auth')->forgetGuards();

        return $this->json($metodo, $ruta, $datos, $token === null
            ? []
            : ['Authorization' => 'Bearer '.$token]);
    }

    private function login(Usuario $usuario)
    {
        return $this->api('POST', '/api/v1/auth/login', [
            'correo' => $usuario->correo,
            'password' => self::PASSWORD,
        ]);
    }

    private function token(Usuario $usuario): string
    {
        $token = $this->login($usuario)->assertOk()->json('data.token');
        $this->assertIsString($token);

        return $token;
    }

    public static function rolesSuspendibles(): array
    {
        return ['candidato' => ['candidato'], 'empresa' => ['empresa']];
    }

    #[DataProvider('rolesSuspendibles')]
    public function test_admin_suspende_revoca_y_reactiva_sin_restaurar_tokens(
        string $rol
    ): void {
        $admin = $this->usuario('admin');
        $afectado = $this->usuario($rol);
        $otro = $this->usuario($rol);

        $tokenAdmin = $this->token($admin);
        $tokenUno = $this->token($afectado);
        $tokenDos = $this->token($afectado);
        $tokenOtro = $this->token($otro);
        $this->assertSame(2, $afectado->tokens()->count());

        $this->api('PATCH', '/api/v1/admin/usuarios/'.$afectado->id.'/suspender', [
            'motivo' => 'Suspension ficticia de QA-05',
        ], $tokenAdmin)
            ->assertOk()
            ->assertJsonPath('data.estado_cuenta', 'suspendido')
            ->assertJsonMissingPath('data.password');

        $actual = $afectado->fresh();
        $this->assertSame('suspendido', $actual->estado_cuenta);
        $this->assertSame('Suspension ficticia de QA-05', $actual->motivo_suspension);
        $this->assertNotNull($actual->fecha_suspension);
        $this->assertSame(0, $actual->tokens()->count());

        foreach ([$tokenUno, $tokenDos] as $revocado) {
            $this->assertNull(PersonalAccessToken::findToken($revocado));
            $this->api('GET', '/api/v1/auth/me', [], $revocado)
                ->assertUnauthorized()
                ->assertJsonPath('error_code', 'UNAUTHENTICATED');
        }

        $this->login($afectado)->assertForbidden();
        $this->api('GET', '/api/v1/auth/me', [], $tokenOtro)
            ->assertOk()->assertJsonPath('data.id', $otro->id);
        $this->api('GET', '/api/v1/auth/me', [], $tokenAdmin)
            ->assertOk()->assertJsonPath('data.rol', 'admin');

        $this->api('PATCH', '/api/v1/admin/usuarios/'.$afectado->id.'/reactivar', [], $tokenAdmin)
            ->assertOk()->assertJsonPath('data.estado_cuenta', 'activo');

        $reactivado = $afectado->fresh();
        $this->assertNull($reactivado->motivo_suspension);
        $this->assertNull($reactivado->fecha_suspension);
        $this->assertSame(0, $reactivado->tokens()->count());

        foreach ([$tokenUno, $tokenDos] as $revocado) {
            $this->api('GET', '/api/v1/auth/me', [], $revocado)
                ->assertUnauthorized();
        }

        $nuevo = $this->token($reactivado);
        $this->api('GET', '/api/v1/auth/me', [], $nuevo)
            ->assertOk()->assertJsonPath('data.id', $afectado->id);
    }

    public static function operacionesNoAutorizadas(): array
    {
        return [
            'candidato suspende' => ['candidato', 'suspender', 'activo'],
            'empresa suspende' => ['empresa', 'suspender', 'activo'],
            'candidato reactiva' => ['candidato', 'reactivar', 'suspendido'],
            'empresa reactiva' => ['empresa', 'reactivar', 'suspendido'],
        ];
    }

    #[DataProvider('operacionesNoAutorizadas')]
    public function test_otro_rol_no_administra_cuentas_ni_revoca_tokens(
        string $rol,
        string $operacion,
        string $estado
    ): void {
        $actor = $this->usuario($rol);
        $objetivo = $this->usuario('candidato', $estado);
        $tokenActor = $this->token($actor);
        // Preparacion del dato para comprobar ausencia de revocacion ajena.
        $tokenObjetivo = $objetivo->createToken('dato-aislado')->plainTextToken;
        $antes = $objetivo->fresh()->getAttributes();

        $this->api('PATCH', '/api/v1/admin/usuarios/'.$objetivo->id.'/'.$operacion, [], $tokenActor)
            ->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');

        $this->assertSame($antes, $objetivo->fresh()->getAttributes());
        $this->assertNotNull(PersonalAccessToken::findToken($tokenObjetivo));
    }

    public static function operacionesSobreAdmin(): array
    {
        return [
            'suspender admin' => ['suspender', 'USER_SUSPENSION_NOT_ALLOWED'],
            'reactivar admin' => ['reactivar', 'USER_REACTIVATION_NOT_ALLOWED'],
        ];
    }

    #[DataProvider('operacionesSobreAdmin')]
    public function test_admin_no_puede_suspender_o_reactivar_una_cuenta_admin(
        string $operacion,
        string $codigo
    ): void {
        $admin = $this->usuario('admin');
        $token = $this->token($admin);

        $this->api('PATCH', '/api/v1/admin/usuarios/'.$admin->id.'/'.$operacion, [], $token)
            ->assertUnprocessable()->assertJsonPath('error_code', $codigo);

        $this->assertSame('activo', $admin->fresh()->estado_cuenta);
        $this->assertNotNull(PersonalAccessToken::findToken($token));
    }

    public function test_motivo_invalido_no_suspende_ni_revoca(): void
    {
        $admin = $this->usuario('admin');
        $objetivo = $this->usuario('candidato');
        $tokenAdmin = $this->token($admin);
        $tokenObjetivo = $this->token($objetivo);

        $this->api('PATCH', '/api/v1/admin/usuarios/'.$objetivo->id.'/suspender', [
            'motivo' => str_repeat('x', 256),
        ], $tokenAdmin)
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors('motivo');

        $this->assertSame('activo', $objetivo->fresh()->estado_cuenta);
        $this->assertNotNull(PersonalAccessToken::findToken($tokenObjetivo));
    }

    public function test_usuario_inexistente_no_muta_ni_revoca(): void
    {
        $admin = $this->usuario('admin');
        $token = $this->token($admin);

        $this->api('PATCH', '/api/v1/admin/usuarios/999999/suspender', [], $token)
            ->assertNotFound()->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');

        $this->assertSame('activo', $admin->fresh()->estado_cuenta);
        $this->assertNotNull(PersonalAccessToken::findToken($token));
    }

    public static function identidadSuspendida(): array
    {
        return [
            'candidato auth me' => ['candidato', '/api/v1/auth/me'],
            'empresa auth me' => ['empresa', '/api/v1/auth/me'],
            'candidato perfil usuario' => ['candidato', '/api/v1/auth/usuarios/me'],
            'empresa perfil usuario' => ['empresa', '/api/v1/auth/usuarios/me'],
        ];
    }

    #[DataProvider('identidadSuspendida')]
    public function test_estado_suspendido_en_bd_bloquea_identidad_con_token_existente(
        string $rol,
        string $ruta
    ): void {
        $usuario = $this->usuario($rol);
        $token = $this->token($usuario);

        $this->api('GET', $ruta, [], $token)->assertOk();

        // Mismo supuesto ya cubierto por SanctumAccesoCandidatoTest:
        // el estado cambia sin borrar previamente el token.
        DB::table('usuarios')->where('id', $usuario->id)->update([
            'estado_cuenta' => 'suspendido',
        ]);

        $this->assertNotNull(PersonalAccessToken::findToken($token));
        $this->api('GET', $ruta, [], $token)
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN')
            ->assertJsonPath('reason', 'ACCOUNT_NOT_ACTIVE');
    }
}
