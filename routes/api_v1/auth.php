<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;



Route::prefix('auth')->group(function () {
    // 1. POST /api/v1/auth/login
    Route::post('/login', [AuthController::class, 'login']);

    // 2. GET /api/v1/auth/usuarios/{id}
    Route::get('/usuarios/{id}', [AuthController::class, 'show']);

    // 3. PUT /api/v1/auth/usuarios/{id}/password
    Route::put('/usuarios/{id}/password', [AuthController::class, 'cambiarPassword']);

    // 4. POST /api/v1/auth/logout
    Route::post('/logout', [AuthController::class, 'logout']);

    // 5. POST /api/v1/auth/forgot-password
    Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
});