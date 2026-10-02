<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;



Route::prefix('auth')->group(function () {
    // Rutas públicas (sin token)
    // POST /api/v1/auth/login
    Route::post('/login', [AuthController::class, 'login']);

    // POST /api/v1/auth/forgot-password
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

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