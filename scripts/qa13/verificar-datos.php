<?php

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

if (
    $app->configurationIsCached()
    || ! $app->environment('local')
    || DB::connection()->getDriverName() !== 'mysql'
    || DB::connection()->getConfig('host') !== 'mysql'
    || DB::selectOne('SELECT DATABASE() AS nombre')->nombre
        !== 'portal_empleos_calidad_qa'
) {
    throw new RuntimeException('Se requiere la base local aislada de QA.');
}

$modelos = [
    'Rol', 'Pais', 'AreaEstudio', 'TipoEstudio', 'Idioma',
    'Institucion', 'Puesto', 'Organizacion', 'Habilidad',
    'Carrera', 'EstadoPostulacion', 'MotivoRechazo',
    'Usuario', 'Candidato', 'Empresa',
];

$conteos = [];

foreach ($modelos as $nombre) {
    $clase = 'App\\Models\\'.$nombre;
    $tabla = (new $clase)->getTable();
    $conteos[$tabla] = DB::table($tabla)->count();
}

ksort($conteos);

$esperados = [
    'admin@qa.example.test' => ['admin', 'activo'],
    'empresa1@qa.example.test' => ['empresa', 'activo'],
    'empresa2@qa.example.test' => ['empresa', 'activo'],
    'candidato1@qa.example.test' => ['candidato', 'activo'],
    'candidato2@qa.example.test' => ['candidato', 'activo'],
    'suspendido@qa.example.test' => ['candidato', 'suspendido'],
];

foreach ($esperados as $correo => [$rol, $estado]) {
    $cuentas = DB::table('usuarios')
        ->join('roles', 'roles.id', '=', 'usuarios.rol_id')
        ->where('usuarios.correo', $correo)
        ->where('roles.nombre', $rol)
        ->where('usuarios.estado_cuenta', $estado)
        ->count();

    if ($cuentas !== 1) {
        throw new RuntimeException('Fixture ausente, duplicado o incompatible: '.$correo);
    }
}

echo json_encode([
    'base' => 'portal_empleos_calidad_qa',
    'conteos' => $conteos,
    'fixtures_verificados' => count($esperados),
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
