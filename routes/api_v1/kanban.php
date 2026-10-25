<?php

use App\Http\Controllers\Api\V1\KanbanController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'rol:empresa'])
    ->group(function () {
        Route::get('/motivos-rechazo', [
            KanbanController::class,
            'motivosRechazo',
        ]);

        Route::prefix('empresas/me/vacantes/{vacante}/postulaciones')
            ->whereNumber('vacante')
            ->group(function () {
                Route::get('/', [
                    KanbanController::class,
                    'tablero',
                ])->withTrashed();

                Route::get('/historial', [
                    KanbanController::class,
                    'historial',
                ])->withTrashed();

                Route::patch('/{postulacion}/avanzar', [
                    KanbanController::class,
                    'avanzar',
                ])
                    ->whereNumber('postulacion')
                    ->withTrashed();

                Route::patch('/{postulacion}/rechazar', [
                    KanbanController::class,
                    'rechazar',
                ])
                    ->whereNumber('postulacion')
                    ->withTrashed();

                Route::patch('/{postulacion}/contratar', [
                    KanbanController::class,
                    'contratar',
                ])
                    ->whereNumber('postulacion')
                    ->withTrashed();
            });
    });