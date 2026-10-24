<?php

use App\Http\Controllers\Api\V1\VacanteCandidatoController;
use App\Http\Controllers\Api\V1\VacanteEmpresaController;
use Illuminate\Support\Facades\Route;

Route::get('/niveles-experiencia', [
    VacanteEmpresaController::class,
    'nivelesExperiencia',
]);

Route::get('/vacantes', [
    VacanteCandidatoController::class,
    'index',
])
    ->middleware(['auth:sanctum', 'rol:candidato'])
    ->name('vacantes.index');

Route::prefix('empresas/me/vacantes')
    ->middleware(['auth:sanctum', 'rol:empresa'])
    ->group(function () {
        // VAC-02: registrar una vacante pendiente de revisión.
        Route::post('/', [
            VacanteEmpresaController::class,
            'store',
        ]);

        Route::get('/{vacante}', [
            VacanteEmpresaController::class,
            'show',
        ])
            ->whereNumber('vacante')
            ->withTrashed();
    });
