<?php

use App\Http\Controllers\Api\V1\CatalogoController;
use Illuminate\Support\Facades\Route;

/*
| Catálogos públicos 
*/
Route::get('/paises', [CatalogoController::class, 'paises']);
Route::get('/tipos-estudio', [CatalogoController::class, 'tiposEstudio']);
Route::get('/instituciones', [CatalogoController::class, 'instituciones']);
Route::get('/idiomas', [CatalogoController::class, 'idiomas']);
Route::get('/puestos', [CatalogoController::class, 'puestos']);
Route::get('/organizaciones', [CatalogoController::class, 'organizaciones']);
