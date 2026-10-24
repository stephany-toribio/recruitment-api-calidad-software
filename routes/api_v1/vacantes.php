<?php

use App\Http\Controllers\Api\V1\VacanteEmpresaController;
use Illuminate\Support\Facades\Route;

// VAC-01: niveles de experiencia. Acceso público.
Route::get('/niveles-experiencia', [
    VacanteEmpresaController::class,
    'nivelesExperiencia',
]);

Route::prefix('empresas/me/vacantes')
    ->middleware(['auth:sanctum', 'rol:empresa'])
    ->group(function () {
        // VAC-02: registrar una vacante pendiente de revisión.
        Route::post('/', [
            VacanteEmpresaController::class,
            'store',
        ]);

        // VAC-03: consultar una vacante propia, incluso eliminada.
        Route::get('/{vacante}', [
            VacanteEmpresaController::class,
            'show',
        ])
            ->whereNumber('vacante')
            ->withTrashed();
    });