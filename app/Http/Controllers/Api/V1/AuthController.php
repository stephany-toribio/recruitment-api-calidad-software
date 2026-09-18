<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\Api\V1\UsuarioResource;
use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseApiController
{
    public function login(LoginRequest $request): JsonResponse
    {
        $usuario = Usuario::where('correo', $request->correo)->first();

        if (!$usuario) {
            return $this->errorResponse('Credenciales incorrectas.', 401);
        }

        // 1. Verificar si la cuenta está bloqueada temporalmente
        if ($usuario->bloqueado_hasta && $usuario->bloqueado_hasta->isFuture()) {
            return $this->errorResponse(
                'Credenciales incorrectas.',
                429
            );
        }

        // 2. Validar contraseña contra password_hash
        if (!Hash::check($request->password, $usuario->password)) {
            $usuario->increment('intentos_fallidos');

            if ($usuario->intentos_fallidos >= 5) {
                $usuario->update([
                    'bloqueado_hasta' => Carbon::now()->addMinutes(30),
                ]);
            }

            return $this->errorResponse(
                'Credenciales incorrectas.',
                401,
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
            'usuario' => new UsuarioResource($usuario),
            'token'   => $token,
        ], 'Inicio de sesión exitoso');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->successResponse(
            new UsuarioResource($request->user()),
            'Usuario autenticado'
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return $this->successResponse(null, 'Sesión cerrada correctamente');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        return $this->successResponse(
            null,
            'Se ha enviado el enlace de restablecimiento a su correo.'
        );
    }
}