<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RolMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $usuario = $request->user();

        if (!$usuario || !$usuario->rol || !in_array($usuario->rol->nombre, $roles, true)) {
            // Mismo formato que el resto de errores 403 de la API (bootstrap/app.php)
            return response()->json([
                'success'    => false,
                'message'    => 'No tienes permisos para realizar esta acción.',
                'error_code' => 'FORBIDDEN',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
