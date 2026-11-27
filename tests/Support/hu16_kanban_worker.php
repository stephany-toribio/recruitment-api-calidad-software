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
                && str_contains($consulta, '`empleos`')
                && str_contains($consulta, 'for update')
            ) {
                responderKanbanHu16([
                    'evento' => 'INTENTO_VACANTE',
                    'nivel_transaccion' => $conexion->transactionLevel(),
                ]);
            }

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

    if (($entrada['modo'] ?? 'servicio') === 'http') {
        $token = $entrada['token'] ?? null;

        if (! is_string($token) || $token === '') {
            throw new RuntimeException('Token HTTP requerido.');
        }

        $app->instance(
            \App\Services\Postulaciones\NotificacionPostulacionService::class,
            \Mockery::mock(
                \App\Services\Postulaciones\NotificacionPostulacionService::class
            )->shouldReceive('notificarDecision')
                ->zeroOrMoreTimes()
                ->andReturnNull()
                ->getMock()
        );

        $url = sprintf(
            '/api/v1/empresas/me/vacantes/%d/postulaciones/%d/contratar',
            $entrada['vacante_id'],
            $entrada['postulacion_id']
        );

        $request = \Illuminate\Http\Request::create(
            $url,
            'PATCH',
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
                'HTTP_AUTHORIZATION' => 'Bearer '.$token,
            ],
            '{}'
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

        responderKanbanHu16([
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
