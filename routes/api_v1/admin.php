<?php

use App\Http\Controllers\Api\V1\Admin\InstitucionController;
use App\Http\Controllers\Api\V1\Admin\NivelPuestoController;
use App\Http\Controllers\Api\V1\Admin\OrganizacionController;
use App\Http\Controllers\Api\V1\Admin\PaisController;
use App\Http\Controllers\Api\V1\Admin\PuestoController;
use App\Http\Controllers\Api\V1\Admin\UsuarioController;
use App\Http\Controllers\Api\V1\AreaEstudioController;
use App\Http\Controllers\Api\V1\HabilidadController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')
    ->middleware(['auth:sanctum', 'rol:admin'])
    ->group(function () {

        Route::get('/usuarios', [UsuarioController::class, 'index']);

        Route::patch(
            '/usuarios/{usuario}/suspender',
            [UsuarioController::class, 'suspender']
        );

        Route::patch(
            '/usuarios/{usuario}/reactivar',
            [UsuarioController::class, 'reactivar']
        );

        Route::get(
            '/areas-estudio',
            [AreaEstudioController::class, 'adminIndex']
        );

        Route::post(
            '/areas-estudio',
            [AreaEstudioController::class, 'store']
        );

        Route::patch(
            '/areas-estudio/{areaEstudio}',
            [AreaEstudioController::class, 'update']
        );

        Route::patch(
            '/areas-estudio/{areaEstudio}/desactivar',
            [AreaEstudioController::class, 'desactivar']
        );

        Route::patch(
            '/areas-estudio/{areaEstudio}/reactivar',
            [AreaEstudioController::class, 'reactivar']
        );

        Route::get(
            '/habilidades',
            [HabilidadController::class, 'adminIndex']
        );

        Route::post(
            '/habilidades',
            [HabilidadController::class, 'store']
        );

        Route::patch(
            '/habilidades/{habilidad}',
            [HabilidadController::class, 'update']
        );

        Route::patch(
            '/habilidades/{habilidad}/desactivar',
            [HabilidadController::class, 'desactivar']
        );

        Route::patch(
            '/habilidades/{habilidad}/reactivar',
            [HabilidadController::class, 'reactivar']
        );

        Route::get('/paises', [PaisController::class, 'index']);
        Route::post('/paises', [PaisController::class, 'store']);
        Route::patch('/paises/{pais}', [PaisController::class, 'update']);
        Route::patch('/paises/{pais}/desactivar', [PaisController::class, 'desactivar']);
        Route::patch('/paises/{pais}/reactivar', [PaisController::class, 'reactivar']);

        Route::get('/instituciones', [InstitucionController::class, 'index']);
        Route::post('/instituciones', [InstitucionController::class, 'store']);
        Route::patch('/instituciones/{institucion}', [InstitucionController::class, 'update']);
        Route::patch('/instituciones/{institucion}/desactivar', [InstitucionController::class, 'desactivar']);
        Route::patch('/instituciones/{institucion}/reactivar', [InstitucionController::class, 'reactivar']);

        Route::get('/puestos', [PuestoController::class, 'index']);
        Route::post('/puestos', [PuestoController::class, 'store']);
        Route::patch('/puestos/{puesto}', [PuestoController::class, 'update']);
        Route::patch('/puestos/{puesto}/desactivar', [PuestoController::class, 'desactivar']);
        Route::patch('/puestos/{puesto}/reactivar', [PuestoController::class, 'reactivar']);

        Route::get('/niveles-puesto', [NivelPuestoController::class, 'index']);
        Route::post('/niveles-puesto', [NivelPuestoController::class, 'store']);
        Route::patch('/niveles-puesto/{nivelPuesto}', [NivelPuestoController::class, 'update']);
        Route::patch('/niveles-puesto/{nivelPuesto}/desactivar', [NivelPuestoController::class, 'desactivar']);
        Route::patch('/niveles-puesto/{nivelPuesto}/reactivar', [NivelPuestoController::class, 'reactivar']);

        Route::get('/organizaciones', [OrganizacionController::class, 'index']);
        Route::post('/organizaciones', [OrganizacionController::class, 'store']);
        Route::patch('/organizaciones/{organizacion}', [OrganizacionController::class, 'update']);
        Route::patch('/organizaciones/{organizacion}/desactivar', [OrganizacionController::class, 'desactivar']);
        Route::patch('/organizaciones/{organizacion}/reactivar', [OrganizacionController::class, 'reactivar']);
    });
