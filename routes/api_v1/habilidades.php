<?php

use App\Http\Controllers\Api\V1\AreaEstudioController;
use App\Http\Controllers\Api\V1\HabilidadController;
use Illuminate\Support\Facades\Route;

Route::get('/habilidades', [HabilidadController::class, 'index']);
Route::get('/areas-estudio', [AreaEstudioController::class, 'index']);
