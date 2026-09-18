<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Auth\CambiarPasswordRequest;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UsuarioResource;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends BaseApiController
{

    public function login(LoginRequest $request): JsonResponse
    {
        $usuario = Usuario::where('correo', $request->correo)->with('rol')->first();

        if (!$usuario) {
            return $this->errorResponse('Credenciales incorrectas.', Response::HTTP_UNAUTHORIZED);
        }

        // 1. Verificar si la cuenta está bloqueada temporalmente
        if ($usuario->bloqueado_hasta && $usuario->bloqueado_hasta->isFuture()) {
            return $this->errorResponse(
                'Cuenta bloqueada temporalmente por múltiples intentos fallidos. Intente más tarde.',
                Response::HTTP_TOO_MANY_REQUESTS
            );
        }

        // 2. Validar contraseña contra hash
        if (!Hash::check($request->password, $usuario->password)) {
            $usuario->increment('intentos_fallidos');

            if ($usuario->intentos_fallidos >= 5) {
                $usuario->update([
                    'bloqueado_hasta' => Carbon::now()->addMinutes(30),
                ]);
            }

            return $this->errorResponse(
                'Credenciales incorrectas.',
                Response::HTTP_UNAUTHORIZED,
                [
                    'intentos_fallidos' => $usuario->intentos_fallidos,
                ]
            );
        }

        // 3. Login exitoso: reiniciar contadores
        $usuario->update([
            'intentos_fallidos' => 0,
            'bloqueado_hasta'   => null,
        ]);

        $token = $usuario->createToken('auth_token')->plainTextToken;

        return $this->successResponse([
            'usuario_id' => $usuario->id,
            'correo'     => $usuario->correo,
            'rol'        => $usuario->rol?->nombre,
            'token'      => $token,
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
     * GET /api/v1/auth/usuarios/{id}
     * Devuelve el perfil completo del usuario con rol y perfil asociado (candidato/empresa).
     */
    public function show(int $id): JsonResponse
    {
        $usuario = Usuario::with(['rol', 'candidato', 'empresa'])->findOrFail($id);

        return $this->successResponse(
            new UsuarioResource($usuario),
            'Perfil de usuario obtenido exitosamente.'
        );
    }

    /**
     * PUT /api/v1/auth/usuarios/{id}/password
     * Cambia la contraseña verificando la contraseña actual.
     */
    public function cambiarPassword(CambiarPasswordRequest $request, int $id): JsonResponse
    {
        $usuario = Usuario::findOrFail($id);

        if (!Hash::check($request->password_actual, $usuario->password)) {
            return $this->errorResponse(
                'La contraseña actual es incorrecta.',
                Response::HTTP_UNPROCESSABLE_ENTITY,
                ['password_actual' => ['La contraseña actual no coincide.']]
            );
        }

        $usuario->update(['password' => $request->password_nuevo]);

        return $this->successResponse(null, 'Contraseña actualizada correctamente.');
    }

    /**
     * POST /api/v1/auth/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->successResponse(null, 'Sesión cerrada correctamente');
    }

    /**
     * POST /api/v1/auth/forgot-password
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        return $this->successResponse(
            null,
            'Se ha enviado el enlace de restablecimiento a su correo.'
        );
    }
}