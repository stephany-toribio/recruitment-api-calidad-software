<?php

use App\Http\Controllers\Api\V1\VacanteCandidatoController;
use Illuminate\Support\Facades\Route;

Route::get(
    '/candidatos/me/postulaciones/{postulacion}/vacante',
    [VacanteCandidatoController::class, 'historico']
)
    ->whereNumber('postulacion')
    ->middleware(['auth:sanctum', 'rol:candidato'])
    ->name('candidatos.postulaciones.vacante');
