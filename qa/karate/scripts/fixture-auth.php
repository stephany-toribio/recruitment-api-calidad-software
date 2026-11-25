<?php

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../../vendor/autoload.php';

$app = require __DIR__.'/../../../bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

try {
    if (
        $app->configurationIsCached()
        || ! $app->environment('local')
        || DB::connection()->getDriverName() !== 'mysql'
        || DB::connection()->getConfig('host') !== 'mysql'
        || DB::selectOne('SELECT DATABASE() AS nombre')->nombre
            !== 'portal_empleos_calidad_qa'
    ) {
        throw new RuntimeException(
            'Entorno QA no autorizado.'
        );
    }

    $entrada = json_decode(
        stream_get_contents(STDIN),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    $runId = $entrada['run_id'] ?? '';

    if (! preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/D',
        $runId
    )) {
        throw new RuntimeException(
            'Identificador de ejecucion invalido.'
        );
    }

    $correo = 'karate-'.$runId.'@qa.example.test';

    $accion = $entrada['accion'] ?? '';

    if ($accion === 'crear') {
        $password = $entrada['password'] ?? '';

        if (! is_string($password) || strlen($password) < 16) {
            throw new RuntimeException(
                'Password temporal insuficiente.'
            );
        }

        $usuario = DB::transaction(
            function () use ($correo, $password) {
                if (
                    Usuario::where('correo', $correo)->exists()
                ) {
                    throw new RuntimeException(
                        'El fixture ya existe.'
                    );
                }

                $rolId = Rol::where('nombre', 'admin')
                    ->firstOrFail()->id;

                $usuario = new Usuario;

                $usuario->fill([
                    'correo' => $correo,
                    'password' => $password,
                    'rol_id' => $rolId,
                    'estado_cuenta' => 'activo',
                    'intentos_fallidos' => 0,
                    'bloqueado_hasta' => null,
                ]);

                $usuario->save();

                return $usuario;
            }
        );

        echo json_encode([
            'success' => true,
            'usuario_id' => $usuario->id,
            'correo' => $correo,
        ], JSON_THROW_ON_ERROR).PHP_EOL;

    } elseif ($accion === 'limpiar') {
        $eliminados = DB::transaction(
            function () use ($correo) {
                $usuario = Usuario::where('correo', $correo)
                    ->lockForUpdate()
                    ->first();

                if (! $usuario) {
                    return 0;
                }

                if ($usuario->rol?->nombre !== 'admin') {
                    throw new RuntimeException(
                        'Rol inesperado. Limpieza cancelada.'
                    );
                }

                if (
                    DB::table('revisiones_vacante')
                        ->where('admin_id', $usuario->id)
                        ->exists()
                    || DB::table('empresas')
                        ->where('usuario_id', $usuario->id)
                        ->exists()
                    || DB::table('candidatos')
                        ->where('usuario_id', $usuario->id)
                        ->exists()
                ) {
                    throw new RuntimeException(
                        'Existen dependencias funcionales.'
                    );
                }

                $usuario->tokens()->delete();

                DB::table('password_reset_tokens')
                    ->where('usuario_id', $usuario->id)
                    ->delete();

                $usuario->delete();

                return 1;
            }
        );

        echo json_encode([
            'success' => true,
            'eliminados' => $eliminados,
            'fixture_existente' => Usuario::where(
                'correo',
                $correo
            )->exists(),
        ], JSON_THROW_ON_ERROR).PHP_EOL;

    } else {
        throw new RuntimeException(
            'Accion no permitida.'
        );
    }
} catch (Throwable $error) {
    // Evitar imprimir consultas o credenciales.
    fwrite(
        STDERR,
        'ERROR fixture QA: '.get_class($error).PHP_EOL
    );

    exit(1);
}
