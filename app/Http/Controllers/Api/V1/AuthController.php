<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\Auth\AuthService;
use App\Services\Auth\UsuarioService;
use App\Http\Requests\Auth\CambiarPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UsuarioResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends BaseApiController
{

    public function login(
        LoginRequest $request,
        AuthService $authService
    ): JsonResponse {
        $resultado = $authService->autenticar(
            $request->correo,
            $request->password
        );

        if (!$resultado['ok']) {
            return $this->errorResponse(
                $resultado['mensaje'],
                $resultado['codigo'],
                $resultado['errores'] ?? null
            );
        }

        return $this->successResponse([
            'usuario_id' => $resultado['usuario']->id,
            'correo'     => $resultado['usuario']->correo,
            'rol'        => $resultado['usuario']->rol?->nombre,
            'token'      => $resultado['token'],
        ], 'Inicio de sesión exitoso.');
    }

    /**
     * GET /api/v1/auth/me
     */
    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            new UsuarioResource($request->user()),
            'Usuario autenticado'
        );
    }

    /**
     * GET /api/v1/auth/usuarios/me
     * Devuelve el perfil completo del usuario autenticado con rol y perfil asociado (candidato/empresa).
     */
    public function show(Request $request): JsonResponse
    {
        $usuario = $request->user()->load(['rol', 'candidato', 'empresa']);

        return $this->successResponse(
            new UsuarioResource($usuario),
            'Perfil de usuario obtenido exitosamente.'
        );
    }

    /**
     * PUT /api/v1/auth/usuarios/me/password
     * Cambia la contraseña del usuario autenticado verificando la contraseña actual.
     */
    public function cambiarPassword(
        CambiarPasswordRequest $request,
        UsuarioService $usuarioService
    ): JsonResponse {
        $usuario = $request->user();

        if (!Hash::check($request->password_actual, $usuario->password)) {
            return $this->errorResponse(
                'La contraseña actual es incorrecta.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ['password_actual' => ['La contraseña actual no coincide.']]
            );
        }

        $usuarioService->actualizarPassword(
            $usuario,
            $request->password_nuevo
        );

        return $this->successResponse(
            null,
            'Contraseña actualizada correctamente.'
        );
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->successResponse(null, 'Sesión cerrada correctamente');
    }
}