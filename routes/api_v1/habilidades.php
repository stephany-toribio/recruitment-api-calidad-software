<?php

use App\Http\Controllers\Api\V1\HabilidadController;
use Illuminate\Support\Facades\Route;

Route::get('/habilidades', [HabilidadController::class, 'index']);
Route::post('/habilidades', [HabilidadController::class, 'store']);
Route::patch('/habilidades/{id}', [HabilidadController::class, 'update']);
