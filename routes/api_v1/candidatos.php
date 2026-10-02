<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CandidatoController;

Route::post('/candidatos', [CandidatoController::class, 'store']);


Route::middleware('auth:sanctum')->group(function() {

    //candidato ve su propio perfil
    Route::get('/candidatos/me', [CandidatoController::class, 'me']);
    Route::put('/candidatos/me', [CandidatoController::class, 'update']);

    Route::get('/candidatos/{candidato}', [CandidatoController::class, 'show']); 
}); 