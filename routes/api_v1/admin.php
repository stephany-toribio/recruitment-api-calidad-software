<?php

use App\Http\Controllers\Api\V1\Admin\UsuarioController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rutas del módulo Administración (v1)
|--------------------------------------------------------------------------
*/
Route::prefix('admin')
    ->middleware(['auth:sanctum', 'rol:admin'])
    ->group(function () {
        Route::get('/usuarios', [UsuarioController::class, 'index']);
        Route::patch('/usuarios/{usuario}/suspender', [UsuarioController::class, 'suspender']);
        Route::patch('/usuarios/{usuario}/reactivar', [UsuarioController::class, 'reactivar']);
    });