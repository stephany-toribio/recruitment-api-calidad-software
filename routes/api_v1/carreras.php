<?php

use App\Http\Controllers\Api\V1\CarreraController;
use Illuminate\Support\Facades\Route;

/*

*/
Route::get('/carreras', [CarreraController::class, 'index']);
