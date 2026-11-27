<?php

use App\Models\Usuario;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require __DIR__.'/../../../vendor/autoload.php';

$app = require __DIR__.'/../../../bootstrap/app.php';

$app->make(Kernel::class)->bootstrap();

function verificarEntorno($app): void
{
    if (
        $app->configurationIsCached()
        || ! $app->environment('local')
        || DB::connection()->getDriverName() !== 'mysql'
        || DB::connection()->getConfig('host') !== 'mysql'
        || DB::selectOne('SELECT DATABASE() AS nombre')->nombre
            !== 'portal_empleos_calidad_qa'
    ) {
        throw new RuntimeException(
            'Entorno QA no autorizado'
        );
    }
}

function datosVacante(string $runId): array
{
    $nivel = DB::table('niveles_puesto')
        ->whereNull('candidato_id')
        ->where('estado', 'activo')
        ->orderBy('id')
        ->value('id');

    if (! $nivel) {
        throw new RuntimeException(
            'No existe nivel global activo'
        );
    }

    $area = DB::table('areas_estudio')
        ->where('activo', true)
        ->whereExists(function ($query) {
            $query->selectRaw('1')
                ->from('habilidades')
                ->whereColumn(
                    'habilidades.area_estudio_id',
                    'areas_estudio.id'
                )
                ->where('habilidades.activo', true)
                ->where(
                    'habilidades.tipo_habilidad',
                    'tecnica'
                );
        })
        ->orderBy('id')
        ->value('id');

    if (! $area) {
        throw new RuntimeException(
            'No existe area con habilidades tecnicas activas'
        );
    }

    $tecnicas = DB::table('habilidades')
        ->where('activo', true)
        ->where('tipo_habilidad', 'tecnica')
        ->where('area_estudio_id', $area)
        ->orderBy('id')
        ->limit(3)
        ->pluck('id');

    $blandas = DB::table('habilidades')
        ->where('activo', true)
        ->where('tipo_habilidad', 'blanda')
        ->orderBy('id')
        ->limit(5 - $tecnicas->count())
        ->pluck('id');

    $habilidades = $tecnicas
        ->concat($blandas)
        ->unique()
        ->values();

    if ($habilidades->count() !== 5) {
        throw new RuntimeException(
            'Catalogo insuficiente de habilidades'
        );
    }

    $hoy = now('America/Lima');

    return [
        'titulo' => 'QA16 '.$runId.' '.bin2hex(random_bytes(4)),

        'descripcion' => str_repeat(
            'Vacante sintetica para pruebas automatizadas. ',
            3
        ),

        'nivel_puesto_id' => (int) $nivel,

        'area_estudio_id' => (int) $area,

        'tipo_empleo' => 'remoto',

        'ubicacion' => null,

        'salario_min' => 3000,

        'salario_max' => 4500,

        'fecha_inicio_vigencia' => $hoy->toDateString(),

        'fecha_fin_vigencia' =>
            $hoy->copy()->addDays(30)->toDateString(),

        'max_contrataciones' => 2,

        'requisitos' => [
            'Disponibilidad para trabajo remoto',
            'Conocimientos de desarrollo de software',
        ],

        'beneficios' => [
            'Capacitacion profesional',
            'Horario flexible',
        ],

        'habilidades' => $habilidades
            ->map(fn ($id, $indice) => [
                'habilidad_id' => (int) $id,
                'requisito' => $indice === 0
                    ? 'must_have'
                    : 'nice_to_have',
            ])
            ->all(),
    ];
}


function datosPago(): array
{
    return [
        'titular' => 'Titular sintetico QA16',
        'numero_tarjeta' => '4242424242424242',
        'vencimiento' => now()->addYear()->format('m/y'),
        'cvv' => '123',
    ];
}

function exigir(bool $condicion, string $mensaje): void
{
    if (! $condicion) {
        throw new RuntimeException($mensaje);
    }
}

function alcance(string $runId): array
{
    $prefijo = 'karate-'.$runId.'-';

    $usuarios = DB::table('usuarios')
        ->where('correo', 'like', $prefijo.'%@qa.example.test')
        ->get(['id', 'correo']);

    foreach ($usuarios as $usuario) {
        exigir(
            str_starts_with($usuario->correo, $prefijo)
            && str_ends_with($usuario->correo, '@qa.example.test'),
            'Usuario fuera del alcance'
        );
    }

    $ids = $usuarios->pluck('id');

    $empresas = DB::table('empresas')
        ->whereIn('usuario_id', $ids)
        ->pluck('id');

    $candidatos = DB::table('candidatos')
        ->whereIn('usuario_id', $ids)
        ->pluck('id');

    $vacantes = DB::table('empleos')
        ->whereIn('empresa_id', $empresas)
        ->pluck('id');

    $postulaciones = DB::table('postulaciones')
        ->whereIn('empleo_id', $vacantes)
        ->pluck('id');

    return compact(
        'ids',
        'empresas',
        'candidatos',
        'vacantes',
        'postulaciones'
    );
}

function cantidades(string $runId): array
{
    $a = alcance($runId);

    return [
        'success' => true,
        'usuarios' => $a['ids']->count(),
        'empresas' => $a['empresas']->count(),
        'candidatos' => $a['candidatos']->count(),
        'vacantes' => $a['vacantes']->count(),
        'postulaciones' => $a['postulaciones']->count(),

        'habilidades_vacantes' => DB::table('empleo_habilidades')
            ->whereIn('empleo_id', $a['vacantes'])
            ->count(),

        'revisiones' => DB::table('revisiones_vacante')
            ->whereIn('empleo_id', $a['vacantes'])
            ->count(),

        'pagos' => DB::table('pagos_vacante')
            ->whereIn('empleo_id', $a['vacantes'])
            ->count(),

        'historial' => DB::table('historial_postulaciones')
            ->whereIn('postulacion_id', $a['postulaciones'])
            ->count(),

        'notificaciones' => DB::table('notificaciones_postulacion')
            ->whereIn('postulacion_id', $a['postulaciones'])
            ->count(),
    ];
}

function consultarVacante(string $runId, int $id): array
{
    $a = alcance($runId);

    exigir(
        $a['vacantes']->contains($id),
        'Vacante fuera del alcance'
    );

    $vacante = DB::table('empleos')
        ->where('id', $id)
        ->first();

    exigir($vacante !== null, 'Vacante inexistente');

    $habilidades = DB::table('empleo_habilidades')
        ->where('empleo_id', $id)
        ->orderBy('habilidad_id')
        ->get(['habilidad_id', 'tipo_requisito'])
        ->map(fn ($h) => [
            'habilidad_id' => (int) $h->habilidad_id,
            'requisito' => $h->tipo_requisito,
        ])
        ->all();

    $revisiones = DB::table('revisiones_vacante')
        ->where('empleo_id', $id)
        ->orderBy('id')
        ->get();

    $pagos = DB::table('pagos_vacante')
        ->where('empleo_id', $id)
        ->orderBy('id')
        ->get([
            'id',
            'resultado',
            'monto',
            'moneda',
            'tarjeta_ultimos4'
        ]);

    return [
        'success' => true,
        'id' => (int) $vacante->id,
        'empresa_id' => (int) $vacante->empresa_id,
        'titulo' => $vacante->titulo,
        'descripcion' => $vacante->descripcion,
        'estado_empleo' => $vacante->estado_empleo,
        'nivel_puesto_id' => (int) $vacante->nivel_puesto_id,
        'area_estudio_id' => (int) $vacante->area_estudio_id,
        'tipo_empleo' => $vacante->tipo_empleo,
        'ubicacion' => $vacante->ubicacion,
        'salario_min' => (float) $vacante->salario_min,
        'salario_max' => (float) $vacante->salario_max,
        'fecha_inicio_vigencia' => $vacante->fecha_inicio_vigencia,
        'fecha_fin_vigencia' => $vacante->fecha_fin_vigencia,
        'max_contrataciones' => (int) $vacante->numero_vacantes,
        'eliminado_en' => $vacante->deleted_at,
        'habilidades' => $habilidades,
        'revisiones' => $revisiones,
        'pagos' => $pagos,
        'postulaciones' => DB::table('postulaciones')
            ->where('empleo_id', $id)
            ->count(),
    ];
}



function prepararIndisponibilidad(
    string $runId,
    int $vacanteId,
    string $condicion,
    array $candidatos
): array {

    return DB::transaction(function () use (
        $runId, $vacanteId, $condicion, $candidatos
    ) {

        $a = alcance($runId);

        exigir(
            $a['vacantes']->contains($vacanteId),
            'Vacante fuera del alcance'
        );

        $vacante = DB::table('empleos')
            ->where('id', $vacanteId)
            ->lockForUpdate()
            ->first();

        exigir($vacante !== null, 'Vacante inexistente');

        exigir(
            str_starts_with(
                $vacante->titulo,
                'QA16 '.$runId.' '
            ),
            'Titulo fuera del alcance QA'
        );

        exigir(
            $vacante->estado_empleo === 'activa'
            && $vacante->deleted_at === null,
            'Se requiere vacante activa'
        );

        exigir(
            ! DB::table('postulaciones')
                ->where('empleo_id', $vacanteId)
                ->exists(),
            'La vacante debe estar sin postulaciones'
        );

        $hoy = now('America/Lima');

        switch ($condicion) {

            case 'pago_fallido':

                $pagos = DB::table('pagos_vacante')
                    ->where('empleo_id', $vacanteId);

                exigir(
                    $pagos->count() === 1
                    && $pagos->where('resultado', 'exitoso')->exists(),
                    'Se requiere un pago exitoso sintetico'
                );

                DB::table('pagos_vacante')
                    ->where('empleo_id', $vacanteId)
                    ->update(['resultado' => 'fallido']);

                break;

            case 'vencida':

                DB::table('empleos')
                    ->where('id', $vacanteId)
                    ->update([
                        'fecha_inicio_vigencia' =>
                            $hoy->copy()->subDays(30)->toDateString(),
                        'fecha_fin_vigencia' =>
                            $hoy->copy()->subDay()->toDateString(),
                    ]);

                break;

            case 'futura':

                DB::table('empleos')
                    ->where('id', $vacanteId)
                    ->update([
                        'fecha_inicio_vigencia' =>
                            $hoy->copy()->addDays(2)->toDateString(),
                        'fecha_fin_vigencia' =>
                            $hoy->copy()->addDays(30)->toDateString(),
                    ]);

                break;

            case 'completa':

                exigir(
                    count($candidatos) ===
                        (int) $vacante->numero_vacantes,
                    'Cantidad de candidatos incorrecta'
                );

                exigir(
                    count(array_unique($candidatos))
                        === count($candidatos),
                    'Candidatos duplicados'
                );

                $estado = DB::table('estados_postulacion')
                    ->where('nombre', 'Contratado')
                    ->value('id');

                exigir(
                    $estado !== null,
                    'Estado Contratado inexistente'
                );

                foreach ($candidatos as $candidatoId) {

                    exigir(
                        is_int($candidatoId)
                        && $a['candidatos']->contains($candidatoId),
                        'Candidato fuera del alcance'
                    );

                    DB::table('postulaciones')->insert([
                        'candidato_id' => $candidatoId,
                        'empleo_id' => $vacanteId,
                        'estado_postulacion_id' => $estado,
                        'fecha_postulacion' => now(),
                        'retirada_en' => null,
                    ]);
                }

                break;

            case 'empresa_suspendida':

                $usuarioId = DB::table('empresas')
                    ->where('id', $vacante->empresa_id)
                    ->value('usuario_id');

                exigir(
                    $a['ids']->contains($usuarioId),
                    'Usuario empresa fuera del alcance'
                );

                DB::table('usuarios')
                    ->where('id', $usuarioId)
                    ->update([
                        'estado_cuenta' => 'suspendido'
                    ]);

                break;

            default:

                throw new RuntimeException(
                    'Condicion QA no autorizada'
                );
        }

        return consultarVacante($runId, $vacanteId);

    }, 3);
}

function consultarCandidato(string $runId, int $id): array
{
    $a = alcance($runId);

    exigir(
        $a['candidatos']->contains($id),
        'Candidato fuera del alcance'
    );

    $candidato = DB::table('candidatos')
        ->where('id', $id)
        ->first();

    exigir($candidato !== null, 'Candidato inexistente');

    $usuario = DB::table('usuarios')
        ->where('id', $candidato->usuario_id)
        ->first(['id', 'correo', 'rol_id', 'estado_cuenta']);

    return [
        'success' => true,
        'perfil' => (array) $candidato,
        'usuario' => (array) $usuario,
        'estudios' => DB::table('candidato_estudios')
            ->where('candidato_id', $id)->orderBy('id')->get(),
        'idiomas' => DB::table('candidato_idiomas')
            ->where('candidato_id', $id)->orderBy('id')->get(),
        'habilidades' => DB::table('candidato_habilidades')
            ->where('candidato_id', $id)
            ->orderBy('habilidad_id')->get(),
    ];
}

function consultarPostulacion(
    string $runId,
    int $id
): array {

    $a = alcance($runId);

    exigir(
        $a['postulaciones']->contains($id),
        'Postulacion fuera del alcance'
    );

    $postulacion = DB::table('postulaciones')
        ->where('id', $id)
        ->first();

    exigir(
        $postulacion !== null,
        'Postulacion inexistente'
    );

    exigir(
        $a['candidatos']->contains($postulacion->candidato_id),
        'Candidato fuera del alcance'
    );

    $estado = DB::table('estados_postulacion')
        ->where('id', $postulacion->estado_postulacion_id)
        ->value('nombre');

    $historial = DB::table('historial_postulaciones as h')
        ->join(
            'estados_postulacion as e',
            'e.id',
            '=',
            'h.estado_postulacion_id'
        )
        ->where('h.postulacion_id', $id)
        ->orderBy('h.id')
        ->select([
            'h.*',
            'e.nombre as estado_nombre',
        ])
        ->get();

    return [
        'success' => true,
        'id' => (int) $postulacion->id,
        'candidato_id' => (int) $postulacion->candidato_id,
        'empleo_id' => (int) $postulacion->empleo_id,
        'estado_postulacion_id' =>
            (int) $postulacion->estado_postulacion_id,
        'estado_nombre' => $estado,
        'retirada_en' => $postulacion->retirada_en,
        'fecha_postulacion' => $postulacion->fecha_postulacion,
        'historial' => $historial,
        'notificaciones' =>
            DB::table('notificaciones_postulacion')
                ->where('postulacion_id', $id)
                ->count(),
    ];
}

function catalogosNegocio(): array
{
    $estados = DB::table('estados_postulacion')
        ->pluck('id', 'nombre')
        ->all();

    foreach ([
        'Recibido',
        'En Revisión',
        'Entrevista',
        'Contratado',
        'Rechazado'
    ] as $estado) {

        exigir(
            isset($estados[$estado]),
            'Estado no disponible: '.$estado
        );
    }

    $motivos = DB::table('motivos_rechazo')
        ->whereIn(
            'motivo',
            \App\Models\MotivoRechazo::PERMITIDOS
        )
        ->orderBy('id')
        ->get(['id', 'motivo']);

    exigir(
        $motivos->count() >= 2,
        'Catalogo de motivos insuficiente'
    );

    return [
        'success' => true,
        'estados' => $estados,
        'motivos' => $motivos,
        'motivo_inexistente' =>
            (int) DB::table('motivos_rechazo')->max('id') + 1000,
    ];
}

function limpiarNegocio(string $runId): array
{
    return DB::transaction(function () use ($runId) {

        $prefijo = 'karate-'.$runId.'-';

        DB::table('usuarios')
            ->where('correo', 'like', $prefijo.'%@qa.example.test')
            ->lockForUpdate()
            ->get();

        $a = alcance($runId);

        $usuarios = $a['ids'];
        $empresas = $a['empresas'];
        $candidatos = $a['candidatos'];
        $vacantes = $a['vacantes'];
        $postulaciones = $a['postulaciones'];

        DB::table('empleos')
            ->whereIn('id', $vacantes)
            ->lockForUpdate()
            ->get();

        DB::table('postulaciones')
            ->whereIn('id', $postulaciones)
            ->lockForUpdate()
            ->get();

        // No eliminar postulaciones de candidatos externos.
        exigir(
            ! DB::table('postulaciones')
                ->whereIn('empleo_id', $vacantes)
                ->whereNotIn('candidato_id', $candidatos)
                ->exists(),
            'Candidato externo relacionado'
        );

        // No eliminar postulaciones a vacantes externas.
        exigir(
            ! DB::table('postulaciones')
                ->whereIn('candidato_id', $candidatos)
                ->whereNotIn('empleo_id', $vacantes)
                ->exists(),
            'Vacante externa relacionada'
        );

        exigir(
            ! DB::table('revisiones_vacante')
                ->whereIn('empleo_id', $vacantes)
                ->whereNotIn('admin_id', $usuarios)
                ->exists(),
            'Revision administrativa externa'
        );

        exigir(
            ! DB::table('revisiones_vacante')
                ->whereIn('admin_id', $usuarios)
                ->whereNotIn('empleo_id', $vacantes)
                ->exists(),
            'Revision de vacante externa'
        );

        exigir(
            ! DB::table('vacantes_guardadas')
                ->whereIn('empleo_id', $vacantes)
                ->whereNotIn('candidato_id', $candidatos)
                ->exists(),
            'Vacante guardada por candidato externo'
        );

        exigir(
            ! DB::table('vacantes_guardadas')
                ->whereIn('candidato_id', $candidatos)
                ->whereNotIn('empleo_id', $vacantes)
                ->exists(),
            'Candidato guardo vacante externa'
        );

        // QA-16 no crea experiencias ni catalogos personalizados.
        foreach ([
            'experiencias_laborales',
            'niveles_puesto',
            'organizaciones',
            'puestos'
        ] as $tabla) {

            exigir(
                ! DB::table($tabla)
                    ->whereIn('candidato_id', $candidatos)
                    ->exists(),
                'Dependencia inesperada: '.$tabla
            );
        }

        $antes = cantidades($runId);

        $borrados = [];

        foreach ([
            'historial_postulaciones',
            'notificaciones_postulacion'
        ] as $tabla) {

            $borrados[$tabla] = DB::table($tabla)
                ->whereIn('postulacion_id', $postulaciones)
                ->delete();
        }

        $borrados['postulaciones'] = DB::table('postulaciones')
            ->whereIn('id', $postulaciones)
            ->delete();

        foreach ([
            'vacantes_guardadas',
            'pagos_vacante',
            'revisiones_vacante',
            'empleo_habilidades'
        ] as $tabla) {

            $borrados[$tabla] = DB::table($tabla)
                ->whereIn('empleo_id', $vacantes)
                ->delete();
        }

        $borrados['empleos'] = DB::table('empleos')
            ->whereIn('id', $vacantes)
            ->delete();

        foreach ([
            'candidato_estudios',
            'candidato_habilidades',
            'candidato_idiomas'
        ] as $tabla) {

            $borrados[$tabla] = DB::table($tabla)
                ->whereIn('candidato_id', $candidatos)
                ->delete();
        }

        $borrados['personal_access_tokens'] =
            DB::table('personal_access_tokens')
                ->where('tokenable_type', (new Usuario)->getMorphClass())
                ->whereIn('tokenable_id', $usuarios)
                ->delete();

        $borrados['password_reset_tokens'] =
            DB::table('password_reset_tokens')
                ->whereIn('usuario_id', $usuarios)
                ->delete();

        $borrados['candidatos'] = DB::table('candidatos')
            ->whereIn('id', $candidatos)
            ->delete();

        $borrados['empresas'] = DB::table('empresas')
            ->whereIn('id', $empresas)
            ->delete();

        $borrados['usuarios'] = DB::table('usuarios')
            ->whereIn('id', $usuarios)
            ->delete();

        // Verificar con los IDs originales, no solo por UUID.
        foreach ([
            ['usuarios', 'id', $usuarios],
            ['empresas', 'id', $empresas],
            ['candidatos', 'id', $candidatos],
            ['empleos', 'id', $vacantes],
            ['postulaciones', 'id', $postulaciones],
            ['pagos_vacante', 'empleo_id', $vacantes],
            ['revisiones_vacante', 'empleo_id', $vacantes],
            ['empleo_habilidades', 'empleo_id', $vacantes],
            ['vacantes_guardadas', 'empleo_id', $vacantes],
            ['historial_postulaciones', 'postulacion_id', $postulaciones],
            ['notificaciones_postulacion', 'postulacion_id', $postulaciones],
        ] as [$tabla, $campo, $ids]) {

            exigir(
                ! DB::table($tabla)->whereIn($campo, $ids)->exists(),
                'Registros residuales: '.$tabla
            );
        }

        $despues = cantidades($runId);

        foreach ($despues as $campo => $cantidad) {
            if ($campo !== 'success') {
                exigir($cantidad === 0, 'Residuo: '.$campo);
            }
        }

        return [
            'success' => true,
            'eliminados' => $borrados['usuarios'],
            'restantes' => $despues['usuarios'],
            'antes' => $antes,
            'borrados' => $borrados,
            'despues' => $despues,
        ];

    }, 3);
}

try {
    verificarEntorno($app);

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
            'UUID de ejecucion invalido'
        );
    }

    $accion = $entrada['accion'] ?? '';

    if ($accion === 'datos_vacante') {
        $resultado = [
            'success' => true,
            'datos' => datosVacante($runId),
        ];

    } elseif ($accion === 'datos_pago') {
        $resultado = [
            'success' => true,
            'datos' => datosPago(),
            'vencimiento_pasado' => now()->subMonth()->format('m/y'),
            'tarifa' => (float) config('pago_vacantes.tarifa_por_puesto'),
            'unidad' => config('pago_vacantes.unidad_cobro'),
            'moneda' => config('pago_vacantes.moneda'),
        ];

    } elseif ($accion === 'preparar_indisponibilidad') {
        $resultado = prepararIndisponibilidad(
            $runId,
            (int) ($entrada['vacante_id'] ?? 0),
            (string) ($entrada['condicion'] ?? ''),
            $entrada['candidatos'] ?? []
        );

    } elseif ($accion === 'consultar_candidato') {
        $resultado = consultarCandidato(
            $runId,
            (int) ($entrada['candidato_id'] ?? 0)
        );

    } elseif ($accion === 'postulacion_inexistente') {
        $resultado = [
            'success' => true,
            'id' => (int) DB::table('postulaciones')->max('id') + 1000,
        ];

    } elseif ($accion === 'consultar_postulacion') {
        $resultado = consultarPostulacion(
            $runId,
            (int) ($entrada['postulacion_id'] ?? 0)
        );

    } elseif ($accion === 'catalogos_negocio') {
        $resultado = catalogosNegocio();

    } elseif ($accion === 'cantidades') {
        $resultado = cantidades($runId);

    } elseif ($accion === 'consultar_vacante') {
        $resultado = consultarVacante(
            $runId,
            (int) ($entrada['vacante_id'] ?? 0)
        );

    } elseif ($accion === 'limpiar') {
        $resultado = limpiarNegocio($runId);

    } else {
        throw new RuntimeException(
            'Accion no autorizada'
        );
    }

    echo json_encode(
        $resultado,
        JSON_THROW_ON_ERROR
    ).PHP_EOL;

} catch (Throwable $error) {
    fwrite(
        STDERR,
        'ERROR QA16: '.get_class($error).PHP_EOL
    );

    exit(1);
}
