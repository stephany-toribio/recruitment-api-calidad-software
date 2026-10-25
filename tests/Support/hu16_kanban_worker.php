<?php

use App\Exceptions\BusinessException;
use App\Models\Empresa;
use App\Services\Postulaciones\KanbanService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function responderKanbanHu16(array $datos): void
{
    echo json_encode(
        $datos,
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
    ).PHP_EOL;

    flush();
}

try {
    if (
        $app->configurationIsCached()
        || ! $app->environment('testing')
        || DB::connection()->getDriverName() !== 'mysql'
        || DB::selectOne('SELECT DATABASE() AS nombre')->nombre
            !== 'portalempleoshu04testing'
    ) {
        throw new RuntimeException(
            'El proceso requiere la base MySQL aislada de pruebas.'
        );
    }

    $linea = fgets(STDIN);

    if ($linea === false) {
        throw new RuntimeException('Faltan los datos del proceso.');
    }

    $entrada = json_decode(
        $linea,
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    foreach (['empresa_id', 'vacante_id', 'postulacion_id'] as $campo) {
        if (
            ! is_int($entrada[$campo] ?? null)
            || $entrada[$campo] < 1
        ) {
            throw new RuntimeException(
                "Identificador inválido: {$campo}."
            );
        }
    }

    $empresa = Empresa::findOrFail($entrada['empresa_id']);

    DB::connection()->beforeExecuting(
        function (string $sql, array $bindings, $conexion): void {
            $consulta = strtolower(ltrim($sql));

            if (
                str_starts_with($consulta, 'select')
                && str_contains($consulta, '`postulaciones`')
                && str_contains($consulta, 'for update')
            ) {
                responderKanbanHu16([
                    'evento' => 'INTENTO_POSTULACION',
                    'nivel_transaccion' => $conexion->transactionLevel(),
                ]);
            }
        }
    );

    responderKanbanHu16([
        'evento' => 'LISTO',
        'conexion_id' => (int) DB::selectOne(
            'SELECT CONNECTION_ID() AS id'
        )->id,
    ]);

    $orden = fgets(STDIN);

    if ($orden === false || trim($orden) !== 'INICIAR') {
        throw new RuntimeException('Se esperaba la orden INICIAR.');
    }

    try {
        app(KanbanService::class)->contratar(
            $empresa,
            $entrada['vacante_id'],
            $entrada['postulacion_id']
        );

        responderKanbanHu16([
            'evento' => 'RESULTADO',
            'status' => 200,
        ]);
    } catch (BusinessException $error) {
        responderKanbanHu16([
            'evento' => 'RESULTADO',
            'status' => $error->getStatusCode(),
            'error_code' => $error->getErrorCode(),
        ]);
    }
} catch (Throwable $error) {
    fwrite(
        STDERR,
        get_class($error).': '.$error->getMessage().PHP_EOL
    );

    exit(1);
}
