<?php

namespace Tests\Feature\Auth;

use App\Http\Middleware\RolMiddleware;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RolMiddlewareTest extends TestCase
{
    #[DataProvider('casosDeAcceso')]
    public function test_aplica_rol_y_estado_actual(
        ?string $rol,
        ?string $estado,
        int $esperado
    ): void {
        $usuario = null;

        if ($rol !== null) {
            $usuario = new Usuario([
                'estado_cuenta' => $estado,
            ]);

            $modeloRol = new Rol;
            $modeloRol->nombre = $rol;

            $usuario->setRelation('rol', $modeloRol);
        }

        $request = Request::create('/api/v1/prueba', 'GET');
        $request->setUserResolver(fn () => $usuario);

        $continuo = false;

        $response = (new RolMiddleware)->handle(
            $request,
            function () use (&$continuo) {
                $continuo = true;

                return response()->json(['success' => true]);
            },
            'candidato'
        );

        $this->assertSame($esperado, $response->getStatusCode());
        $this->assertSame($esperado === 200, $continuo);

        if ($esperado === 403) {
            $datos = json_decode(
                $response->getContent(),
                true,
                512,
                JSON_THROW_ON_ERROR
            );

            $this->assertFalse($datos['success']);
            $this->assertSame('FORBIDDEN', $datos['error_code']);
        }
    }

    public static function casosDeAcceso(): array
    {
        return [
            'candidato activo' => ['candidato', 'activo', 200],
            'candidato suspendido' => ['candidato', 'suspendido', 403],
            'estado desconocido' => ['candidato', 'otro_estado', 403],
            'estado ausente' => ['candidato', null, 403],
            'empresa activa' => ['empresa', 'activo', 403],
            'administrador activo' => ['admin', 'activo', 403],
            'sin usuario' => [null, null, 403],
        ];
    }
}
