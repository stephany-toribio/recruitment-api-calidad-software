<?php

use App\Models\Usuario;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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

function numeroUnico(
    string $tabla,
    string $campo,
    string $prefijo,
    int $longitud
): string {
    $restantes = $longitud - strlen($prefijo);

    do {
        $numero = $prefijo.str_pad(
            (string) random_int(
                0,
                10 ** $restantes - 1
            ),
            $restantes,
            '0',
            STR_PAD_LEFT
        );
    } while (
        DB::table($tabla)
            ->where($campo, $numero)
            ->exists()
    );

    return $numero;
}

function catalogos(): array
{
    $datos = [
        'tipo_estudio_id' => DB::table('tipos_estudio')
            ->where('nombre', 'Sin estudios formales')
            ->value('id'),

        'area_estudio_id' => DB::table('areas_estudio')
            ->where('activo', true)
            ->value('id'),

        'idioma_id' => DB::table('idiomas')
            ->where('activo', true)
            ->value('id'),

        'habilidad_id' => DB::table('habilidades')
            ->where('activo', true)
            ->where('tipo_habilidad', 'blanda')
            ->value('id'),
    ];

    foreach ($datos as $clave => $valor) {
        if (! $valor) {
            throw new RuntimeException(
                'Catalogo requerido no disponible: '.$clave
            );
        }

        $datos[$clave] = (int) $valor;
    }

    return $datos;
}

function correoDeEjecucion(
    string $correo,
    string $runId
): bool {
    $prefijo = 'karate-'.$runId.'-';

    return str_starts_with($correo, $prefijo)
        && str_ends_with($correo, '@qa.example.test');
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

    $prefijo = 'karate-'.$runId.'-';

    if ($accion === 'datos') {
        $catalogos = catalogos();

        $correo = $prefijo
            .bin2hex(random_bytes(8))
            .'@qa.example.test';

        $tipo = $entrada['tipo'] ?? '';

        if ($tipo === 'candidato') {
            $datos = [
                'nombre' => 'Candidato',
                'apellidos' => 'Ficticio QA15',
                'dni' => numeroUnico(
                    'candidatos', 'dni', '0', 8
                ),
                'fecha_nacimiento' => '2000-01-01',
                'genero' => 'prefiero no decir',
                'correo' => $correo,
                'celular' => numeroUnico(
                    'candidatos', 'celular', '9', 9
                ),
                'busca_primer_empleo' => true,
                'estudios' => [
                    [
                        'tipo_estudio_id' =>
                            $catalogos['tipo_estudio_id'],
                        'area_estudio_id' =>
                            $catalogos['area_estudio_id'],
                    ],
                ],
                'idiomas' => [
                    [
                        'idioma_id' =>
                            $catalogos['idioma_id'],
                        'nivel_escrito' => 'nativo',
                        'nivel_oral' => 'nativo',
                    ],
                ],
                'habilidades' => [
                    $catalogos['habilidad_id'],
                ],
            ];
        } elseif ($tipo === 'empresa') {
            $datos = [
                'correo' => $correo,
                'encargado_empresa' =>
                    'Responsable ficticio QA15',
                'razon_social' =>
                    'Empresa ficticia QA15',
                'sector' => 'Tecnología e Informática',
                'ruc' => numeroUnico(
                    'empresas', 'ruc', '20', 11
                ),
                'sitio_web' =>
                    'https://empresa.example.test',
                'descripcion' =>
                    'Datos sinteticos de automatizacion.',
            ];
        } else {
            throw new RuntimeException(
                'Tipo de registro no autorizado'
            );
        }

        $resultado = [
            'success' => true,
            'datos' => $datos,
        ];

    } elseif ($accion === 'consultar') {
        $correo = $entrada['correo'] ?? '';

        if (! correoDeEjecucion($correo, $runId)) {
            throw new RuntimeException(
                'Correo fuera de la ejecucion'
            );
        }

        $usuario = Usuario::where('correo', $correo)
            ->with(['rol', 'candidato', 'empresa'])
            ->first();

        $resultado = [
            'success' => true,
            'existe' => $usuario !== null,
        ];

        if ($usuario) {
            $password = $entrada['password'] ?? null;

            $resultado += [
                'usuario_id' => $usuario->id,
                'rol' => $usuario->rol?->nombre,
                'estado' => $usuario->estado_cuenta,
                'intentos_fallidos' =>
                    (int) $usuario->intentos_fallidos,
                'bloqueado' =>
                    $usuario->bloqueado_hasta?->isFuture()
                    ?? false,
                'bloqueado_hasta' =>
                    $usuario->bloqueado_hasta?->toISOString(),
                'tokens' => $usuario->tokens()->count(),
                'password_hasheada' =>
                    is_string($password)
                    && $password !== $usuario->password
                    && Hash::check(
                        $password,
                        $usuario->password
                    ),
                'candidato_id' =>
                    $usuario->candidato?->id,
                'empresa_id' =>
                    $usuario->empresa?->id,
                'dni' =>
                    $usuario->candidato?->dni,
                'celular' =>
                    $usuario->candidato?->celular,
                'ruc' =>
                    $usuario->empresa?->ruc,
                'estudios' =>
                    $usuario->candidato?->estudios()->count(),
                'idiomas' =>
                    $usuario->candidato?->idiomas()->count(),
                'habilidades' =>
                    $usuario->candidato?->habilidades()->count(),
                'experiencias' =>
                    $usuario->candidato?->experiencias()->count(),
            ];
        }

    } elseif ($accion === 'crear_admin') {
        $password = $entrada['password'] ?? '';

        if (
            ! is_string($password)
            || strlen($password) < 16
        ) {
            throw new RuntimeException(
                'Password temporal invalida'
            );
        }

        $correo = $prefijo
            .bin2hex(random_bytes(8))
            .'@qa.example.test';

        $rolId = DB::table('roles')
            ->where('nombre', 'admin')
            ->value('id');

        if (! $rolId) {
            throw new RuntimeException(
                'Rol administrador no disponible'
            );
        }

        $usuario = Usuario::create([
            'correo' => $correo,
            'password' => $password,
            'rol_id' => $rolId,
            'estado_cuenta' => 'activo',
            'intentos_fallidos' => 0,
            'bloqueado_hasta' => null,
        ]);

        $resultado = [
            'success' => true,
            'usuario_id' => $usuario->id,
            'correo' => $correo,
            'rol' => 'admin',
        ];

    } elseif (
        $accion === 'suspender'
        || $accion === 'expirar_bloqueo'
    ) {
        $correo = $entrada['correo'] ?? '';

        if (! correoDeEjecucion($correo, $runId)) {
            throw new RuntimeException(
                'Usuario fuera de la ejecucion'
            );
        }

        $resultado = DB::transaction(
            function () use ($correo, $accion) {
                $usuario = Usuario::where('correo', $correo)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($usuario->rol?->nombre !== 'candidato') {
                    throw new RuntimeException(
                        'Se requiere candidato sintetico'
                    );
                }

                if ($accion === 'suspender') {
                    if ($usuario->estado_cuenta !== 'activo') {
                        throw new RuntimeException(
                            'Estado inicial inesperado'
                        );
                    }

                    $usuario->update([
                        'estado_cuenta' => 'suspendido',
                        'motivo_suspension' => 'Fixture QA15',
                        'fecha_suspension' => now(),
                    ]);

                } else {
                    if (
                        (int) $usuario->intentos_fallidos !== 5
                        || ! $usuario->bloqueado_hasta
                        || ! $usuario->bloqueado_hasta->isFuture()
                    ) {
                        throw new RuntimeException(
                            'No existe bloqueo real que expirar'
                        );
                    }

                    $usuario->update([
                        'bloqueado_hasta' => now()->subSecond(),
                    ]);
                }

                return [
                    'success' => true,
                    'estado' => $usuario->estado_cuenta,
                    'intentos_fallidos' =>
                        (int) $usuario->intentos_fallidos,
                    'bloqueado' =>
                        $usuario->bloqueado_hasta?->isFuture()
                        ?? false,
                ];
            }
        );

    } elseif ($accion === 'cantidades') {
        $ids = Usuario::where(
            'correo',
            'like',
            $prefijo.'%@qa.example.test'
        )->pluck('id');

        $candidatos = DB::table('candidatos')
            ->whereIn('usuario_id', $ids)
            ->pluck('id');

        $resultado = [
            'success' => true,
            'usuarios' => $ids->count(),
            'candidatos' => $candidatos->count(),
            'empresas' => DB::table('empresas')
                ->whereIn('usuario_id', $ids)
                ->count(),
            'estudios' => DB::table('candidato_estudios')
                ->whereIn('candidato_id', $candidatos)
                ->count(),
            'idiomas' => DB::table('candidato_idiomas')
                ->whereIn('candidato_id', $candidatos)
                ->count(),
            'habilidades' => DB::table('candidato_habilidades')
                ->whereIn('candidato_id', $candidatos)
                ->count(),
        ];

    } elseif ($accion === 'limpiar') {
        $eliminados = DB::transaction(
            function () use ($prefijo, $runId) {
                $usuarios = Usuario::where(
                    'correo',
                    'like',
                    $prefijo.'%@qa.example.test'
                )
                    ->lockForUpdate()
                    ->get();

                $ids = $usuarios->pluck('id');

                foreach ($usuarios as $usuario) {
                    if (! correoDeEjecucion(
                        $usuario->correo,
                        $runId
                    )) {
                        throw new RuntimeException(
                            'Usuario fuera de alcance'
                        );
                    }
                }

                $candidatos = DB::table('candidatos')
                    ->whereIn('usuario_id', $ids)
                    ->pluck('id');

                $empresas = DB::table('empresas')
                    ->whereIn('usuario_id', $ids)
                    ->pluck('id');

                $dependencias = [
                    ['empleos', 'empresa_id', $empresas],
                    ['postulaciones', 'candidato_id', $candidatos],
                    ['revisiones_vacante', 'admin_id', $ids],
                    ['vacantes_guardadas', 'candidato_id', $candidatos],
                    ['experiencias_laborales', 'candidato_id', $candidatos],
                    ['niveles_puesto', 'candidato_id', $candidatos],
                    ['organizaciones', 'candidato_id', $candidatos],
                    ['puestos', 'candidato_id', $candidatos],
                ];

                foreach ($dependencias as [$tabla, $campo, $valores]) {
                    if (
                        DB::table($tabla)
                            ->whereIn($campo, $valores)
                            ->exists()
                    ) {
                        throw new RuntimeException(
                            'Dependencias funcionales; limpieza cancelada'
                        );
                    }
                }

                foreach ([
                    'candidato_estudios',
                    'candidato_idiomas',
                    'candidato_habilidades'
                ] as $tabla) {
                    DB::table($tabla)
                        ->whereIn('candidato_id', $candidatos)
                        ->delete();
                }

                foreach ($usuarios as $usuario) {
                    $usuario->tokens()->delete();
                }

                DB::table('password_reset_tokens')
                    ->whereIn('usuario_id', $ids)
                    ->delete();

                DB::table('candidatos')
                    ->whereIn('usuario_id', $ids)
                    ->delete();

                DB::table('empresas')
                    ->whereIn('usuario_id', $ids)
                    ->delete();

                DB::table('usuarios')
                    ->whereIn('id', $ids)
                    ->delete();

                return $usuarios->count();
            }
        );

        $resultado = [
            'success' => true,
            'eliminados' => $eliminados,
            'restantes' => Usuario::where(
                'correo',
                'like',
                $prefijo.'%@qa.example.test'
            )->count(),
        ];

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
        'ERROR QA15: '.get_class($error).PHP_EOL
    );

    exit(1);
}
