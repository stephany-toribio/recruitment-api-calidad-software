<?php

use App\Http\Controllers\Api\V1\SeguimientoPostulacionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'rol:empresa'])
        ->group(function() {
            Route::get('/empresas/me/postulaciones/{postulacion}/perfil',
            [SeguimientoPostulacionController::class, 
            'perfil',
            ])->whereNumber('postulacion');
        });

Route::middleware(['auth:sanctum', 'rol:candidato'])
    ->group(function () {
        Route::get('/candidatos/me/postulaciones', [
            SeguimientoPostulacionController::class,
            'misPostulaciones',
        ]);
    });
