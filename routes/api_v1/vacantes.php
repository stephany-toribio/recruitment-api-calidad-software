<?php

use App\Http\Controllers\Api\V1\VacanteCandidatoController;
use App\Http\Controllers\Api\V1\VacanteEmpresaController;
use Illuminate\Support\Facades\Route;

// Catálogo público de niveles del puesto.
Route::get('/niveles-experiencia', [
    VacanteEmpresaController::class,
    'nivelesExperiencia',
]);

// Búsqueda de vacantes para candidatos.
Route::get('/vacantes', [
    VacanteCandidatoController::class,
    'index',
])
    ->middleware(['auth:sanctum', 'rol:candidato'])
    ->name('vacantes.index');

// Detalle de una vacante disponible para candidatos.
Route::get('/vacantes/{vacante}', [
    VacanteCandidatoController::class,
    'show',
])
    ->whereNumber('vacante')
    ->middleware(['auth:sanctum', 'rol:candidato'])
    ->name('vacantes.show');

// Registro, consulta y gestión de vacantes propias.
Route::prefix('empresas/me/vacantes')
    ->middleware(['auth:sanctum', 'rol:empresa'])
    ->group(function () {
        Route::post('/', [
            VacanteEmpresaController::class,
            'store',
        ]);

        Route::get('/eliminadas', [
            VacanteEmpresaController::class,
            'eliminadas',
        ]);

        Route::get('/{vacante}', [
            VacanteEmpresaController::class,
            'show',
        ])
            ->whereNumber('vacante')
            ->withTrashed();

        Route::put('/{vacante}', [
            VacanteEmpresaController::class,
            'update',
        ])
            ->whereNumber('vacante')
            ->withTrashed();

        Route::patch('/{vacante}/pausar', [
            VacanteEmpresaController::class,
            'pausar',
        ])
            ->whereNumber('vacante')
            ->withTrashed();

        Route::patch('/{vacante}/reanudar', [
            VacanteEmpresaController::class,
            'reanudar',
        ])
            ->whereNumber('vacante')
            ->withTrashed();

        Route::patch('/{vacante}/finalizar', [
            VacanteEmpresaController::class,
            'finalizar',
        ])
            ->whereNumber('vacante')
            ->withTrashed();

        Route::delete('/{vacante}', [
            VacanteEmpresaController::class,
            'destroy',
        ])
            ->whereNumber('vacante')
            ->withTrashed();
    });
