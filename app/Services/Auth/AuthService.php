<?php

namespace App\Services\Auth;

use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function autenticar(string $correo, string $password): array
    {
        $usuario = Usuario::where('correo', $correo)
            ->with('rol')
            ->first();

        if (!$usuario) {
            return [
                'ok' => false,
                'codigo' => 401,
                'mensaje' => 'Credenciales incorrectas.',
            ];
        }

        if ($usuario->bloqueado_hasta && $usuario->bloqueado_hasta->isFuture()) {
            return [
                'ok' => false,
                'codigo' => 429,
                'mensaje' => 'Cuenta bloqueada temporalmente. Intente más tarde.',
            ];
        }

        if ($usuario->bloqueado_hasta && $usuario->bloqueado_hasta->isPast()) {
            $usuario->update([
                'intentos_fallidos' => 0,
                'bloqueado_hasta' => null,
            ]);
        }

        if (!Hash::check($password, $usuario->password)) {
            $usuario->increment('intentos_fallidos');

            $usuario->refresh();

            $intentosFallidos = $usuario->intentos_fallidos;
            $intentosRestantes = max(0, 5 - $intentosFallidos);

            if ($usuario->intentos_fallidos >= 5) {
                $usuario->update([
                    'bloqueado_hasta' => Carbon::now()->addMinutes(15),
                ]);

                return [
                    'ok' => false,
                    'codigo' => 429,
                    'mensaje' => 'Cuenta bloqueada temporalmente. Intente más tarde.',
                    'errores' => [
                        'intentos_fallidos' => $intentosFallidos,
                        'intentos_restantes' => 0,
                        'bloqueado_hasta' => $usuario->bloqueado_hasta,
                    ],
                ];
            }

            return [
                'ok' => false,
                'codigo' => 401,
                'mensaje' => 'Credenciales incorrectas.',
                'errores' => [
                    'intentos_fallidos' => $intentosFallidos,
                    'intentos_restantes' => $intentosRestantes,
                ],
            ];
        }

        if ($usuario->estado_cuenta === 'suspendido') {
            return [
                'ok' => false,
                'codigo' => 403,
                'mensaje' => 'Su cuenta se encuentra suspendida. Comuníquese con el administrador.',
            ];
        }

        $usuario->update([
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        $token = $usuario->createToken('auth_token')->plainTextToken;

        return [
            'ok' => true,
            'codigo' => 200,
            'usuario' => $usuario,
            'token' => $token,
        ];
    }
}