<?php

use App\Http\Controllers\Api\V1\Admin\UsuarioController;
use App\Http\Controllers\Api\V1\AreaEstudioController;
use App\Http\Controllers\Api\V1\HabilidadController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'rol:admin'])
    ->group(function () {

        Route::get('/usuarios', [UsuarioController::class, 'index']);

        Route::patch(
            '/usuarios/{usuario}/suspender',
            [UsuarioController::class, 'suspender']
        );

        Route::patch(
            '/usuarios/{usuario}/reactivar',
            [UsuarioController::class, 'reactivar']
        );

        Route::get(
            '/areas-estudio',
            [AreaEstudioController::class, 'adminIndex']
        );

        Route::post(
            '/areas-estudio',
            [AreaEstudioController::class, 'store']
        );

        Route::patch(
            '/areas-estudio/{areaEstudio}',
            [AreaEstudioController::class, 'update']
        );

        Route::patch(
            '/areas-estudio/{areaEstudio}/desactivar',
            [AreaEstudioController::class, 'desactivar']
        );

        Route::patch(
            '/areas-estudio/{areaEstudio}/reactivar',
            [AreaEstudioController::class, 'reactivar']
        );

        Route::get(
            '/habilidades',
            [HabilidadController::class, 'adminIndex']
        );

        Route::post(
            '/habilidades',
            [HabilidadController::class, 'store']
        );

        Route::patch(
            '/habilidades/{habilidad}',
            [HabilidadController::class, 'update']
        );

        Route::patch(
            '/habilidades/{habilidad}/desactivar',
            [HabilidadController::class, 'desactivar']
        );

        Route::patch(
            '/habilidades/{habilidad}/reactivar',
            [HabilidadController::class, 'reactivar']
        );
    });
