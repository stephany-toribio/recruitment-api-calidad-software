<?php

use App\Http\Controllers\Api\V1\EmpresaController;
use Illuminate\Support\Facades\Route;

Route::post('/empresas', [EmpresaController::class, 'store'])
    ->middleware(['precognitive']);

Route::middleware(['auth:sanctum', 'rol:empresa'])->group(function () {
    Route::get('/empresas/me', [EmpresaController::class, 'show']);
    Route::put('/empresas/me', [EmpresaController::class, 'update']);
});
