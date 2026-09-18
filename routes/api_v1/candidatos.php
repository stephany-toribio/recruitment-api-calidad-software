<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\CandidatoController;

Route::post('/candidatos', [CandidatoController::class, 'store']);
Route::get('/candidatos/{id}', [CandidatoController::class, 'show']);
Route::put('/candidatos/{id}', [CandidatoController::class, 'update']);