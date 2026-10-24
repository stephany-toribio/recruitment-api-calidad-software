<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Version 1 (v1)
|--------------------------------------------------------------------------

*/

Route::prefix('v1')->group(function () {
    require __DIR__.'/api_v1/auth.php';
    require __DIR__.'/api_v1/candidatos.php';
    require __DIR__.'/api_v1/catalogos.php';
    require __DIR__.'/api_v1/empresas.php';
    require __DIR__.'/api_v1/habilidades.php';
    require __DIR__.'/api_v1/carreras.php';
    require __DIR__.'/api_v1/vacantes.php';
    require __DIR__.'/api_v1/moderacion.php';
    require __DIR__.'/api_v1/postulaciones.php';
    require __DIR__.'/api_v1/match.php';
    require __DIR__.'/api_v1/admin.php';
});
