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

        if (!$usuario || !$usuario->rol) {
            return response()->json([
                'success' => false,
                'message' => 'FORBIDDEN',
                'errors' => null,
            ], Response::HTTP_FORBIDDEN);
        }

        if (!in_array($usuario->rol->nombre, $roles, true)) {
            return response()->json([
                'success' => false,
                'message' => 'FORBIDDEN',
                'errors' => null,
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}