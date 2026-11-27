<?php

use App\Exceptions\BusinessException;
use App\Models\Candidato;
use App\Services\Postulaciones\PostulacionService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../vendor/autoload.php';

$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

function responderWorker(array $datos): void
{
    echo json_encode(
        $datos,
        JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE
    ).PHP_EOL;

    flush();
}

function esperarOrdenWorker(string $esperada): void
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
        || ! is_int($entrada['vacante_id'] ?? null)
        || $entrada['candidato_id'] < 1
        || $entrada['vacante_id'] < 1
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

            if (str_contains($consulta, '`empleos`')) {
                responderWorker([
                    'evento' => 'INTENTO_BLOQUEO',
                    'nivel_transaccion' => $conexion
                        ->transactionLevel(),
                ]);
            }

            if (str_contains($consulta, '`usuarios`')) {
                responderWorker([
                    'evento' => 'INTENTO_CUENTAS',
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
                || ! str_contains($consulta, '`empleos`')
                || ! str_contains($consulta, 'for update')
            ) {
                return;
            }

            $bloqueoObservado = true;

            responderWorker([
                'evento' => 'BLOQUEO_OBTENIDO',
            ]);

            if ($detenerTrasBloqueo) {
                esperarOrdenWorker('LIBERAR');
            }
        }
    );

    responderWorker([
        'evento' => 'LISTO',
        'conexion_id' => $conexionId,
    ]);

    esperarOrdenWorker('INICIAR');

    if (($entrada['modo'] ?? 'servicio') === 'http') {
        $token = $entrada['token'] ?? null;

        if (! is_string($token) || $token === '') {
            throw new RuntimeException(
                'Falta el token de integracion HTTP.'
            );
        }

        $request = \Illuminate\Http\Request::create(
            '/api/v1/candidatos/me/postulaciones',
            'POST',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            ],
            json_encode([
                'vacante_id' => $entrada['vacante_id'],
            ], JSON_THROW_ON_ERROR)
        );

        $kernel = $app->make(
            \Illuminate\Contracts\Http\Kernel::class
        );

        $response = $kernel->handle($request);

        $body = json_decode(
            $response->getContent(),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $kernel->terminate($request, $response);

        responderWorker([
            'evento' => 'RESULTADO',
            'modo' => 'http',
            'status' => $response->getStatusCode(),
            'success' => $body['success'] ?? null,
            'error_code' => $body['error_code'] ?? null,
            'postulacion_id' => $body['data']['id'] ?? null,
            'estado' => $body['data']['estado']['codigo'] ?? null,
        ]);

        exit(0);
    }

    try {
        $postulacion = app(PostulacionService::class)->postular(
            $candidato,
            $entrada['vacante_id']
        );

        responderWorker([
            'evento' => 'RESULTADO',
            'status' => 201,
            'postulacion_id' => (int) $postulacion->getKey(),
        ]);
    } catch (BusinessException $error) {
        responderWorker([
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
