<?php

use App\Exceptions\BusinessException;
use App\Models\Candidato;
use App\Services\Postulaciones\RetiroPostulacionService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function responderWorkerHu16(array $datos): void
{
    echo json_encode(
        $datos,
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
    ).PHP_EOL;

    flush();
}

function esperarOrdenWorkerHu16(string $esperada): void
{
    $linea = fgets(STDIN);

    if ($linea === false || trim($linea) !== $esperada) {
        throw new RuntimeException(
            "El proceso esperaba la orden {$esperada}."
        );
    }
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
        throw new RuntimeException(
            'Faltan los datos de entrada del proceso.'
        );
    }

    $entrada = json_decode(
        $linea,
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    if (
        ! is_array($entrada)
        || ! is_int($entrada['candidato_id'] ?? null)
        || ! is_int($entrada['postulacion_id'] ?? null)
        || $entrada['candidato_id'] < 1
        || $entrada['postulacion_id'] < 1
        || ! is_bool($entrada['detener_tras_bloqueo'] ?? null)
    ) {
        throw new RuntimeException(
            'Entrada inválida para el proceso de prueba.'
        );
    }

    $candidato = Candidato::findOrFail(
        $entrada['candidato_id']
    );

    $conexionId = (int) DB::selectOne(
        'SELECT CONNECTION_ID() AS id'
    )->id;

    $detenerTrasBloqueo = $entrada['detener_tras_bloqueo'];
    $bloqueoObservado = false;

    DB::connection()->beforeExecuting(
        function (
            string $sql,
            array $bindings,
            $conexion
        ): void {
            $consulta = strtolower(ltrim($sql));

            if (
                ! str_starts_with($consulta, 'select')
                || ! str_contains($consulta, 'for update')
            ) {
                return;
            }

            if (str_contains($consulta, '`usuarios`')) {
                responderWorkerHu16([
                    'evento' => 'INTENTO_CUENTA',
                    'nivel_transaccion' => $conexion
                        ->transactionLevel(),
                ]);
            }

            if (str_contains($consulta, '`postulaciones`')) {
                responderWorkerHu16([
                    'evento' => 'INTENTO_POSTULACION',
                    'nivel_transaccion' => $conexion
                        ->transactionLevel(),
                ]);
            }
        }
    );

    DB::listen(
        function (QueryExecuted $evento) use (
            &$bloqueoObservado,
            $detenerTrasBloqueo
        ): void {
            $consulta = strtolower(ltrim($evento->sql));

            if (
                $bloqueoObservado
                || ! str_starts_with($consulta, 'select')
                || ! str_contains($consulta, '`postulaciones`')
                || ! str_contains($consulta, 'for update')
            ) {
                return;
            }

            $bloqueoObservado = true;

            responderWorkerHu16([
                'evento' => 'POSTULACION_BLOQUEADA',
            ]);

            if ($detenerTrasBloqueo) {
                esperarOrdenWorkerHu16('LIBERAR');
            }
        }
    );

    responderWorkerHu16([
        'evento' => 'LISTO',
        'conexion_id' => $conexionId,
    ]);

    esperarOrdenWorkerHu16('INICIAR');

    try {
        $postulacion = app(RetiroPostulacionService::class)
            ->retirar(
                $candidato,
                $entrada['postulacion_id']
            );

        responderWorkerHu16([
            'evento' => 'RESULTADO',
            'status' => 200,
            'postulacion_id' => (int) $postulacion->getKey(),
            'retirada_en' => $postulacion
                ->retirada_en
                ->copy()
                ->utc()
                ->toIso8601String(),
        ]);
    } catch (BusinessException $error) {
        responderWorkerHu16([
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
