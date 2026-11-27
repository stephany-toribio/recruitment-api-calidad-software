<?php
// Solo lectura; verificar pares e IDs creados por esta corrida, sin borrar fixtures.
require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
try {
    if ($app->configurationIsCached() || ! $app->environment('local')
        || DB::connection()->getDriverName() !== 'mysql'
        || DB::connection()->getConfig('host') !== 'mysql'
        || DB::selectOne('SELECT DATABASE() AS nombre')->nombre !== 'portal_empleos_calidad_qa') {
        throw new RuntimeException('Se requiere QA local aislado y configuracion sin cache.');
    }
    $payload = json_decode(stream_get_contents(STDIN), true, 512, JSON_THROW_ON_ERROR);
    $expected = $payload['expected'] ?? [];
    if (! is_array($expected) || count($expected) === 0 || count($expected) > 20000) {
        throw new RuntimeException('Sin escrituras verificables o limite de auditoria excedido.');
    }
    $pairs = []; $ids = []; $checked = 0; $errors = [];
    DB::transaction(function () use ($expected, &$pairs, &$ids, &$checked, &$errors) {
        foreach ($expected as $record) {
            foreach (['user_id', 'job_id', 'app_id'] as $key) {
                if (! isset($record[$key]) || ! is_int($record[$key]) || $record[$key] <= 0) {
                    throw new RuntimeException('Identificadores de auditoria invalidos.');
                }
            }
            $pair = $record['user_id'].':'.$record['job_id'];
            if (isset($pairs[$pair]) || isset($ids[$record['app_id']])) {
                $errors[] = ['pair' => $pair, 'reason' => 'doble_creacion_reportada'];
                continue;
            }
            $pairs[$pair] = true; $ids[$record['app_id']] = true;
            $user = DB::table('usuarios')->where('id', $record['user_id'])->value('correo');
            if (! is_string($user) || ! preg_match('/^postulacion[0-9]{3}@carga\.qa\.invalid$/', $user)) {
                throw new RuntimeException('Cuenta fuera del perfil de carga autorizado.');
            }
            $rows = DB::table('postulaciones as p')->join('candidatos as c', 'c.id', '=', 'p.candidato_id')
                ->where('c.usuario_id', $record['user_id'])->where('p.empleo_id', $record['job_id'])
                ->pluck('p.id')->all();
            if (count($rows) !== 1 || (int) $rows[0] !== $record['app_id']) {
                $errors[] = ['pair' => $pair, 'reason' => 'perdida_duplicacion_o_id_distinto', 'count' => count($rows)];
            }
            $checked++;
        }
    });
    echo json_encode(['success' => count($errors) === 0, 'checked' => $checked, 'errors' => $errors], JSON_THROW_ON_ERROR).PHP_EOL;
    exit(count($errors) === 0 ? 0 : 1);
} catch (Throwable $error) {
    echo json_encode(['success' => false, 'checked' => 0, 'error' => $error->getMessage()]).PHP_EOL;
    exit(1);
}
