<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RecuperacionPasswordController;
use Illuminate\Support\Facades\Route;



Route::prefix('auth')->group(function () {
    // Rutas públicas (sin token)
    // POST /api/v1/auth/login
    Route::post('/login', [AuthController::class, 'login']);

    // Recuperación de contraseña por código al correo (HU-11)
    // POST /api/v1/auth/forgot-password    -> envía el código
    Route::post('/forgot-password', [RecuperacionPasswordController::class, 'solicitar']);
    // POST /api/v1/auth/verify-reset-code  -> valida el código y devuelve reset_token
    Route::post('/verify-reset-code', [RecuperacionPasswordController::class, 'verificarCodigo']);
    // POST /api/v1/auth/reset-password     -> establece la nueva contraseña
    Route::post('/reset-password', [RecuperacionPasswordController::class, 'restablecer']);

    // Rutas protegidas con Sanctum (header Authorization: Bearer {token})
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // GET /api/v1/auth/usuarios/me
        Route::get('/usuarios/me', [AuthController::class, 'show']);

        // PUT /api/v1/auth/usuarios/me/password
        Route::put('/usuarios/me/password', [AuthController::class, 'cambiarPassword']);
    });
});