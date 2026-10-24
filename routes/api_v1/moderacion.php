<?php

use App\Http\Controllers\Api\V1\Admin\RevisionVacanteController;
use App\Http\Controllers\Api\V1\MisVacantesController;
use App\Http\Controllers\Api\V1\PagoVacanteController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Moderación de vacantes (HU-22) y pago simulado (HU-03)
|--------------------------------------------------------------------------
*/

Route::prefix('admin/vacantes')
    ->middleware(['auth:sanctum', 'rol:admin'])
    ->group(function () {
        Route::get('/pendientes', [RevisionVacanteController::class, 'pendientes']);
        Route::post('/aprobar-masivo', [RevisionVacanteController::class, 'aprobarMasivo']);

        Route::get('/{vacante}', [RevisionVacanteController::class, 'show'])->whereNumber('vacante');
        Route::patch('/{vacante}/aprobar', [RevisionVacanteController::class, 'aprobar'])->whereNumber('vacante');
        Route::patch('/{vacante}/no-aprobar', [RevisionVacanteController::class, 'noAprobar'])->whereNumber('vacante');
    });

Route::prefix('empresas/me/vacantes')
    ->middleware(['auth:sanctum', 'rol:empresa'])
    ->group(function () {
        Route::get('/', [MisVacantesController::class, 'index']);

        // withTrashed: una vacante eliminada responde 409 VACANCY_DELETED (lo decide el servicio), no 404
        Route::get('/{vacante}/pago', [PagoVacanteController::class, 'show'])->whereNumber('vacante')->withTrashed();
        Route::post('/{vacante}/pago', [PagoVacanteController::class, 'store'])->whereNumber('vacante')->withTrashed();
        Route::post('/{vacante}/pago/cancelar', [PagoVacanteController::class, 'cancelar'])->whereNumber('vacante')->withTrashed();
    });
