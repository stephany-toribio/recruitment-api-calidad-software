<?php

namespace Tests\Feature\Auth;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SanctumAccesoCandidatoTest extends TestCase
{
    private const RUTA = '/api/_tests/hu04-acceso';

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame(
            'sqlite',
            DB::connection()->getDriverName()
        );

        $this->assertSame(
            ':memory:',
            DB::connection()->getDatabaseName()
        );

        $this->assertSame(
            Usuario::class,
            config('auth.providers.users.model')
        );

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });

        Schema::create('usuarios', function (Blueprint $table): void {
            $table->id();
            $table->string('correo')->unique();
            $table->string('password');
            $table->foreignId('rol_id')->constrained('roles');
            $table->string('estado_cuenta');
            $table->timestamps();
        });

        $migracion = require database_path(
            'migrations/2026_09_18_171423_create_personal_access_tokens_table.php'
        );

        $migracion->up();

        Route::get(self::RUTA, function (Request $request) {
            return response()->json([
                'success' => true,
                'usuario_id' => $request->user()->getKey(),
            ]);
        })->middleware(['auth:sanctum', 'rol:candidato']);
    }

    private function crearUsuario(string $nombreRol): Usuario
    {
        $rol = Rol::create(['nombre' => $nombreRol]);

        return Usuario::create([
            'correo' => $nombreRol.'@example.test',
            'password' => 'PruebaSegura123!',
            'rol_id' => $rol->id,
            'estado_cuenta' => 'activo',
        ]);
    }

    private function consultar(?string $token = null)
    {
        $this->app->make('auth')->forgetGuards();

        $headers = $token === null
            ? []
            : ['Authorization' => 'Bearer '.$token];

        return $this->getJson(self::RUTA, $headers);
    }

    public function test_sin_token_responde_401(): void
    {
        $this->consultar()
            ->assertUnauthorized()
            ->assertJson([
                'success' => false,
                'error_code' => 'UNAUTHENTICATED',
            ]);
    }

    public function test_token_alterado_responde_401(): void
    {
        $usuario = $this->crearUsuario('candidato');
        $token = $usuario->createToken('prueba')->plainTextToken;

        $this->consultar($token.'alterado')
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');
    }

    public function test_candidato_activo_con_token_real_puede_acceder(): void
    {
        $usuario = $this->crearUsuario('candidato');
        $token = $usuario->createToken('prueba')->plainTextToken;

        $this->consultar($token)
            ->assertOk()
            ->assertJson([
                'success' => true,
                'usuario_id' => $usuario->id,
            ]);
    }

    #[DataProvider('rolesNoPermitidos')]
    public function test_otro_rol_con_token_valido_recibe_403(
        string $rol
    ): void {
        $usuario = $this->crearUsuario($rol);
        $token = $usuario->createToken('prueba')->plainTextToken;

        $this->consultar($token)
            ->assertForbidden()
            ->assertJson([
                'success' => false,
                'error_code' => 'FORBIDDEN',
            ]);
    }

    public static function rolesNoPermitidos(): array
    {
        return [
            'empresa' => ['empresa'],
            'administrador' => ['admin'],
        ];
    }

    public function test_suspension_directa_en_bd_bloquea_token_existente(): void
    {
        $usuario = $this->crearUsuario('candidato');
        $nuevoToken = $usuario->createToken('prueba');

        $this->consultar($nuevoToken->plainTextToken)
            ->assertOk();

        DB::table('usuarios')
            ->where('id', $usuario->id)
            ->update(['estado_cuenta' => 'suspendido']);

        $this->assertDatabaseHas('personal_access_tokens', [
            'id' => $nuevoToken->accessToken->id,
        ]);

        $this->consultar($nuevoToken->plainTextToken)
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');
    }

    public function test_token_revocado_responde_401(): void
    {
        $usuario = $this->crearUsuario('candidato');
        $nuevoToken = $usuario->createToken('prueba');

        $this->consultar($nuevoToken->plainTextToken)
            ->assertOk();

        $nuevoToken->accessToken->delete();

        $this->consultar($nuevoToken->plainTextToken)
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');
    }

    public function test_token_expirado_responde_401(): void
    {
        $usuario = $this->crearUsuario('candidato');

        $token = $usuario->createToken(
            'prueba-expirada',
            ['*'],
            now()->subMinute()
        )->plainTextToken;

        $this->consultar($token)
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');
    }
}
