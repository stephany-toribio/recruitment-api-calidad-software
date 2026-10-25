<?php

use App\Http\Controllers\Api\V1\PostulacionController;
use App\Http\Controllers\Api\V1\VacanteCandidatoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'rol:candidato'])
    ->group(function () {
        Route::post('/candidatos/me/postulaciones', [
            PostulacionController::class,
            'store',
        ])
            ->name('candidatos.postulaciones.store');

        Route::get(
            '/candidatos/me/postulaciones/{postulacion}/vacante',
            [VacanteCandidatoController::class, 'historico']
        )
            ->whereNumber('postulacion')
            ->name('candidatos.postulaciones.vacante');
    });
