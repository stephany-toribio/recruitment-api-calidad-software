<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->redirectGuestsTo(function ($request) {
            return null;
        });
        // Registra el middleware globalmente
        $middleware->alias([
            'rol' => \App\Http\Middleware\RolMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // 1. Asegura que cualquier excepción en la API devuelva JSON evitando redirecciones HTML
        $exceptions->shouldRenderJsonWhen(function ($request, Throwable $e) {
            return $request->is('api/*');
        });

        // 2. Excepciones de Reglas de Negocio (409 Conflict, 422 Unprocessable, etc.)
        $exceptions->render(function (\App\Exceptions\BusinessException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'message'    => $e->getMessage(),
                    'error_code' => $e->getErrorCode(),
                ], $e->getStatusCode());
            }
        });

        // 3. Errores de Validación de FormRequests (422) con contrato estandarizado
        $exceptions->render(function (\Illuminate\Validation\ValidationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'message'    => 'Los datos proporcionados no son válidos.',
                    'error_code' => 'VALIDATION_ERROR',
                    'errors'     => $e->errors(),
                ], 422);
            }
        });

        // 4. Recursos no encontrados: ModelNotFoundException o rutas no encontradas (404)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'message'    => 'El recurso solicitado no fue encontrado.',
                    'error_code' => 'RESOURCE_NOT_FOUND',
                ], 404);
            }
        });

        // 5. Método HTTP no permitido para una ruta existente (405)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'message'    => 'Método HTTP no permitido para este recurso.',
                    'error_code' => 'METHOD_NOT_ALLOWED',
                ], 405);
            }
        });

        // 6. No autenticado (401)
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'message'    => 'No autenticado o token inválido.',
                    'error_code' => 'UNAUTHENTICATED',
                ], 401);
            }
        });

        // 7. No autorizado / Permisos insuficientes (403)
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException|\Illuminate\Auth\Access\AuthorizationException $e, $request) {
            if ($request->is('api/*')) {
                return response()->json([
                    'success'    => false,
                    'message'    => 'No tienes permisos para realizar esta acción.',
                    'error_code' => 'FORBIDDEN',
                ], 403);
            }
        });

        // 8. Captura específicamente modelos de Eloquent que no existan
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, $request) {
            if ($request->is('api/*')) {
                $nombreModelo = class_basename($e->getModel()); // ej: "Candidato", "Usuario", etc.
                return response()->json([
                    'success'    => false,
                    'message'    => "El {$nombreModelo} solicitado no fue encontrado.",
                    'error_code' => 'RESOURCE_NOT_FOUND', 
                ], 404);
            }
        });


    })->create();
