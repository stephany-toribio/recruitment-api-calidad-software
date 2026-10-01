<?php

use App\Http\Controllers\Api\V1\HabilidadController;
use Illuminate\Support\Facades\Route;

// Pública: catálogo de habilidades (lo usa el formulario de registro)
Route::get('/habilidades', [HabilidadController::class, 'index']);

// Protegidas: crear y editar el catálogo requiere token
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/habilidades', [HabilidadController::class, 'store']);
    Route::patch('/habilidades/{habilidad}', [HabilidadController::class, 'update']);
});
