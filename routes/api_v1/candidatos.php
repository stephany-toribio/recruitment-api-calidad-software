<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CandidatoController;

Route::post('/candidatos', [CandidatoController::class, 'store'])
    ->middleware(['precognitive']);


Route::middleware(['auth:sanctum', 'rol:candidato'])->group(function() {

    //candidato ve su propio perfil
    Route::get('/candidatos/me', [CandidatoController::class, 'me']);
    Route::put('/candidatos/me', [CandidatoController::class, 'update']);

    // catálogo global + sus puestos personalizados (para agregar experiencias desde el perfil)
    Route::get('/candidatos/me/puestos', [CandidatoController::class, 'puestos']);

}); 

Route::middleware(['auth:sanctum', 'rol:empresa,admin'])->group(function () {
    Route::get('/candidatos/{candidato}', [CandidatoController::class, 'show']);
});