<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\RecuperacionPasswordController;
use Illuminate\Support\Facades\Route;



Route::prefix('auth')->group(function () {
    // POST /api/v1/auth/login
    Route::post('/login', [AuthController::class, 'login']);

    // POST /api/v1/auth/forgot-password
    Route::post('/forgot-password', [RecuperacionPasswordController::class, 'solicitar']);
    // POST /api/v1/auth/verify-reset-code
    Route::post('/verify-reset-code', [RecuperacionPasswordController::class, 'verificarCodigo']);
    // POST /api/v1/auth/reset-password
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