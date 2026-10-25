<?php

namespace Tests\MySql;

use App\Exceptions\BusinessException;
use App\Models\Candidato;
use App\Models\Empleo;
use App\Models\EstadoPostulacion;
use App\Models\Postulacion;
use App\Models\Usuario;
use App\Services\Postulaciones\PostulacionService;
use App\Services\Postulaciones\RetiroPostulacionService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class RetirarPostulacionMySqlTest extends TestCase
{
    private bool $transaccionIniciada = false;

    private Candidato $candidato;

    private Empleo $vacante;

    private Postulacion $postulacion;

    private int $empresaUsuarioId;

    protected function setUp(): void
    {
        parent::setUp();

        if (
            app()->configurationIsCached()
            || ! app()->environment('testing')
            || DB::connection()->getDriverName() !== 'mysql'
            || DB::selectOne('SELECT DATABASE() AS nombre')->nombre
                !== 'portalempleoshu04testing'
        ) {
            throw new RuntimeException(
                'Estas pruebas requieren la base MySQL aislada portalempleoshu04testing.'
            );
        }

        DB::beginTransaction();
        $this->transaccionIniciada = true;

        Carbon::setTestNow(
            Carbon::create(
                2026,
                10,
                25,
                12,
                0,
                0,
                'America/Lima'
            )
        );

        $this->prepararDatos();
    }

    protected function tearDown(): void
    {
        try {
            if ($this->transaccionIniciada) {
                DB::rollBack();
            }
        } finally {
            Carbon::setTestNow();

            parent::tearDown();
        }
    }

    #[DataProvider('etapasPermitidas')]
    public function test_retira_conservando_etapa_datos_e_historial(
        string $etapa
    ): void {
        $this->cambiarEtapa($etapa);

        $antes = $this->registroPostulacion();

        $historialAntes = DB::table('historial_postulaciones')
            ->where('postulacion_id', $this->postulacion->getKey())
            ->orderBy('id')
            ->get()
            ->map(fn ($fila): array => (array) $fila)
            ->all();

        $vacanteAntes = (array) DB::table('empleos')
            ->where('id', $this->vacante->getKey())
            ->first();

        $notificacionesAntes = DB::table('notificaciones_postulacion')
            ->where('postulacion_id', $this->postulacion->getKey())
            ->count();

        $resultado = $this->retirar();

        $this->assertSame(
            $this->postulacion->getKey(),
            $resultado->getKey()
        );
        $this->assertSame($etapa, $resultado->estado->nombre);
        $this->assertTrue($resultado->estaRetirada());
        $this->assertTrue($resultado->retirada_en->equalTo(now()));

        $despues = $this->registroPostulacion();

        $this->assertNotNull($despues['retirada_en']);

        // Solo la fecha de retiro y updated_at pueden cambiar.
        unset($antes['retirada_en'], $antes['updated_at']);
        unset($despues['retirada_en'], $despues['updated_at']);

        $this->assertSame($antes, $despues);

        $this->assertSame(
            $historialAntes,
            DB::table('historial_postulaciones')
                ->where('postulacion_id', $this->postulacion->getKey())
                ->orderBy('id')
                ->get()
                ->map(fn ($fila): array => (array) $fila)
                ->all()
        );

        $this->assertSame(
            $vacanteAntes,
            (array) DB::table('empleos')
                ->where('id', $this->vacante->getKey())
                ->first()
        );

        $this->assertSame(
            $notificacionesAntes,
            DB::table('notificaciones_postulacion')
                ->where('postulacion_id', $this->postulacion->getKey())
                ->count()
        );

        $this->assertFalse(
            Postulacion::query()
                ->activas()
                ->whereKey($resultado->getKey())
                ->exists()
        );

        $this->assertDatabaseHas('postulaciones', [
            'id' => $resultado->getKey(),
            'candidato_id' => $this->candidato->getKey(),
            'empleo_id' => $this->vacante->getKey(),
        ]);
    }

    public static function etapasPermitidas(): array
    {
        return [
            'recibido' => [EstadoPostulacion::RECIBIDO],
            'en revision' => [EstadoPostulacion::EN_REVISION],
            'entrevista' => [EstadoPostulacion::ENTREVISTA],
        ];
    }

    #[DataProvider('etapasTerminales')]
    public function test_rechaza_retiro_terminal_sin_modificar_registro(
        string $etapa
    ): void {
        $this->cambiarEtapa($etapa);
        $antes = $this->registroPostulacion();

        $this->assertRechazo(
            fn () => $this->retirar(),
            409,
            'APPLICATION_NOT_WITHDRAWABLE'
        );

        $this->assertSame(
            $antes,
            $this->registroPostulacion()
        );
    }

    public static function etapasTerminales(): array
    {
        return [
            'contratado' => [EstadoPostulacion::CONTRATADO],
            'rechazado' => [EstadoPostulacion::RECHAZADO],
        ];
    }

    #[DataProvider('situacionesDeVacante')]
    public function test_disponibilidad_de_vacante_no_impide_retiro(
        string $situacion
    ): void {
        switch ($situacion) {
            case 'pausada':
                $this->vacante->update([
                    'estado_empleo' => Empleo::PAUSADA,
                ]);
                break;

            case 'finalizada':
                $this->vacante->update([
                    'estado_empleo' => Empleo::FINALIZADA,
                ]);
                break;

            case 'eliminada':
                $this->vacante->delete();
                break;

            case 'vencida':
                $this->vacante->update([
                    'fecha_inicio_vigencia' => '2026-10-23',
                    'fecha_fin_vigencia' => '2026-10-24',
                ]);
                break;

            case 'empresa_suspendida':
                DB::table('usuarios')
                    ->where('id', $this->empresaUsuarioId)
                    ->update(['estado_cuenta' => 'suspendido']);
                break;
        }

        $vacanteAntes = (array) DB::table('empleos')
            ->where('id', $this->vacante->getKey())
            ->first();

        $resultado = $this->retirar();

        $this->assertTrue($resultado->estaRetirada());
        $this->assertSame(
            EstadoPostulacion::RECIBIDO,
            $resultado->estado->nombre
        );

        $this->assertSame(
            $vacanteAntes,
            (array) DB::table('empleos')
                ->where('id', $this->vacante->getKey())
                ->first()
        );
    }

    public static function situacionesDeVacante(): array
    {
        return [
            'pausada' => ['pausada'],
            'finalizada' => ['finalizada'],
            'eliminada' => ['eliminada'],
            'vencida' => ['vencida'],
            'empresa suspendida' => ['empresa_suspendida'],
        ];
    }

    public function test_retiro_repetido_conserva_la_primera_fecha(): void
    {
        $this->retirar();
        $antes = $this->registroPostulacion();

        Carbon::setTestNow(now()->addHours(2));

        $this->assertRechazo(
            fn () => $this->retirar(),
            409,
            'APPLICATION_ALREADY_WITHDRAWN'
        );

        $this->assertSame(
            $antes,
            $this->registroPostulacion()
        );
    }

    public function test_otro_candidato_no_puede_retirar(): void
    {
        $otroCandidato = $this->crearCandidato();
        $antes = $this->registroPostulacion();

        $this->assertRechazo(
            fn () => app(RetiroPostulacionService::class)->retirar(
                $otroCandidato,
                (int) $this->postulacion->getKey()
            ),
            403,
            'FORBIDDEN'
        );

        $this->assertSame(
            $antes,
            $this->registroPostulacion()
        );
    }

    public function test_postulacion_inexistente_responde_404(): void
    {
        $id = (int) $this->postulacion->getKey();

        $this->postulacion->delete();

        $this->assertRechazo(
            fn () => app(RetiroPostulacionService::class)->retirar(
                $this->candidato,
                $id
            ),
            404,
            'RESOURCE_NOT_FOUND'
        );
    }

    public function test_candidato_suspendido_no_puede_retirar(): void
    {
        $antes = $this->registroPostulacion();

        DB::table('usuarios')
            ->where('id', $this->candidato->usuario_id)
            ->update(['estado_cuenta' => 'suspendido']);

        $this->assertRechazo(
            fn () => $this->retirar(),
            403,
            'FORBIDDEN'
        );

        $this->assertSame(
            $antes,
            $this->registroPostulacion()
        );
    }

    public function test_candidato_no_guardado_no_puede_retirar(): void
    {
        $this->assertRechazo(
            fn () => app(RetiroPostulacionService::class)->retirar(
                new Candidato,
                (int) $this->postulacion->getKey()
            ),
            403,
            'FORBIDDEN'
        );
    }

    public function test_hu05_rechaza_repostular_despues_del_retiro(): void
    {
        $this->retirar();
        $antes = $this->registroPostulacion();

        $this->assertRechazo(
            fn () => app(PostulacionService::class)->postular(
                $this->candidato,
                (int) $this->vacante->getKey()
            ),
            409,
            'DUPLICATE_APPLICATION'
        );

        $this->assertSame(
            $antes,
            $this->registroPostulacion()
        );

        $this->assertSame(
            1,
            DB::table('postulaciones')
                ->where('candidato_id', $this->candidato->getKey())
                ->where('empleo_id', $this->vacante->getKey())
                ->count()
        );
    }

    public function test_mysql_conserva_unicidad_despues_del_retiro(): void
    {
        $this->retirar();

        $duplicado = $this->registroPostulacion();
        unset($duplicado['id']);

        $this->expectException(
            UniqueConstraintViolationException::class
        );

        DB::table('postulaciones')->insert($duplicado);
    }

    public function test_fallo_despues_del_update_revierte_el_retiro(): void
    {
        $antes = $this->registroPostulacion();
        $nivelAntes = DB::transactionLevel();
        $updateObservado = false;
        $falloObservado = false;

        $original = Postulacion::getEventDispatcher();

        if ($original === null) {
            throw new RuntimeException(
                'Falta el dispatcher de Eloquent.'
            );
        }

        $temporal = clone $original;

        $temporal->listen(
            'eloquent.updated: '.Postulacion::class,
            function (Postulacion $postulacion) use (
                &$updateObservado
            ): void {
                $updateObservado = DB::table('postulaciones')
                    ->where('id', $postulacion->getKey())
                    ->whereNotNull('retirada_en')
                    ->exists();

                throw new RuntimeException(
                    'HU16_FALLO_CONTROLADO_DESPUES_DEL_UPDATE'
                );
            }
        );

        Postulacion::setEventDispatcher($temporal);

        try {
            $this->retirar();
        } catch (RuntimeException $error) {
            if (
                $error->getMessage()
                !== 'HU16_FALLO_CONTROLADO_DESPUES_DEL_UPDATE'
            ) {
                throw $error;
            }

            $falloObservado = true;
        } finally {
            Postulacion::setEventDispatcher($original);
        }

        $this->assertTrue($updateObservado);
        $this->assertTrue($falloObservado);
        $this->assertSame($antes, $this->registroPostulacion());
        $this->assertSame(
            $nivelAntes,
            DB::transactionLevel()
        );
    }

    #[DataProvider('etapasPermitidas')]
    public function test_http_retira_y_devuelve_solo_los_campos_acordados(
        string $etapa
    ): void {
        $this->cambiarEtapa($etapa);

        $respuesta = $this->retirarHttp($this->tokenHttp());

        $respuesta
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.id',
                (int) $this->postulacion->getKey()
            )
            ->assertJsonPath('data.estado.nombre', $etapa)
            ->assertJsonPath(
                'data.estado.codigo',
                EstadoPostulacion::codigoDe($etapa)
            )
            ->assertJsonPath('data.retirada', true);

        $datos = $respuesta->json('data');

        $this->assertSame([
            'id',
            'estado',
            'retirada',
            'retirada_en',
        ], array_keys($datos));

        $this->assertSame(
            ['codigo', 'nombre'],
            array_keys($datos['estado'])
        );

        $this->assertTrue(
            Carbon::parse($datos['retirada_en'])->equalTo(now())
        );

        $this->assertNotNull(
            DB::table('postulaciones')
                ->where('id', $this->postulacion->getKey())
                ->value('retirada_en')
        );
    }

    public function test_http_sin_token_responde_401_antes_de_validar(): void
    {
        $antes = $this->registroPostulacion();

        $this->retirarHttp(null, ['candidato_id' => 999])
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');

        $this->assertSame($antes, $this->registroPostulacion());
    }

    public function test_http_token_alterado_responde_401(): void
    {
        $this->retirarHttp($this->tokenHttp().'alterado')
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');

        $this->assertNull(
            $this->registroPostulacion()['retirada_en']
        );
    }

    #[DataProvider('rolesNoAutorizadosHttp')]
    public function test_http_otro_rol_responde_403(
        string $rol
    ): void {
        $token = $this->tokenHttp(
            $this->crearUsuario($rol)
        );

        $this->retirarHttp($token)
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');

        $this->assertNull(
            $this->registroPostulacion()['retirada_en']
        );
    }

    public static function rolesNoAutorizadosHttp(): array
    {
        return [
            'empresa' => ['empresa'],
            'administrador' => ['admin'],
        ];
    }

    public function test_http_postulacion_ajena_responde_403_sin_modificarla(): void
    {
        $otroCandidato = $this->crearCandidato();
        $antes = $this->registroPostulacion();

        $token = $this->tokenHttp(
            (int) $otroCandidato->usuario_id
        );

        $this->retirarHttp($token)
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');

        $this->assertSame($antes, $this->registroPostulacion());
    }

    public function test_http_candidato_sin_perfil_responde_403(): void
    {
        $token = $this->tokenHttp(
            $this->crearUsuario('candidato')
        );

        $this->retirarHttp($token)
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');

        $this->assertNull(
            $this->registroPostulacion()['retirada_en']
        );
    }

    public function test_http_candidato_suspendido_no_retira(): void
    {
        $token = $this->tokenHttp();
        $antes = $this->registroPostulacion();

        DB::table('usuarios')
            ->where('id', $this->candidato->usuario_id)
            ->update(['estado_cuenta' => 'suspendido']);

        $this->retirarHttp($token)
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');

        $this->assertSame($antes, $this->registroPostulacion());
    }

    #[DataProvider('etapasTerminales')]
    public function test_http_estado_terminal_responde_409(
        string $etapa
    ): void {
        $this->cambiarEtapa($etapa);
        $antes = $this->registroPostulacion();

        $this->retirarHttp($this->tokenHttp())
            ->assertConflict()
            ->assertJsonPath(
                'error_code',
                'APPLICATION_NOT_WITHDRAWABLE'
            );

        $this->assertSame($antes, $this->registroPostulacion());
    }

    public function test_http_retiro_repetido_conserva_fecha_original(): void
    {
        $token = $this->tokenHttp();

        $this->retirarHttp($token)->assertOk();
        $antes = $this->registroPostulacion();

        Carbon::setTestNow(now()->addHour());

        $this->retirarHttp($token)
            ->assertConflict()
            ->assertJsonPath(
                'error_code',
                'APPLICATION_ALREADY_WITHDRAWN'
            );

        $this->assertSame($antes, $this->registroPostulacion());
    }

    public function test_http_postulacion_inexistente_responde_404(): void
    {
        $token = $this->tokenHttp();
        $id = (int) $this->postulacion->getKey();

        $this->postulacion->delete();

        $this->retirarHttp($token, [], (string) $id)
            ->assertNotFound()
            ->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');
    }

    #[DataProvider('identificadoresInvalidosHttp')]
    public function test_http_identificador_invalido_no_modifica_postulacion(
        string $identificador
    ): void {
        $antes = $this->registroPostulacion();

        $this->retirarHttp(
            $this->tokenHttp(),
            [],
            $identificador
        )
            ->assertNotFound()
            ->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');

        $this->assertSame($antes, $this->registroPostulacion());
    }

    public static function identificadoresInvalidosHttp(): array
    {
        return [
            'cero' => ['0'],
            'negativo' => ['-1'],
            'decimal' => ['1.5'],
            'texto' => ['abc'],
            'fuera de rango' => ['999999999999999999999999999999'],
        ];
    }

    public function test_http_rechaza_body_adicional(): void
    {
        $antes = $this->registroPostulacion();

        $this->retirarHttp(
            $this->tokenHttp(),
            ['retirada_en' => '2000-01-01']
        )
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['retirada_en']);

        $this->assertSame($antes, $this->registroPostulacion());
    }

    public function test_http_rechaza_parametros_de_consulta(): void
    {
        $antes = $this->registroPostulacion();
        $token = $this->tokenHttp();

        $this->reiniciarAutenticacionHttp();

        $this->patchJson(
            $this->rutaRetiroHttp().'?candidato_id=999',
            [],
            ['Authorization' => 'Bearer '.$token]
        )
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['candidato_id']);

        $this->assertSame($antes, $this->registroPostulacion());
    }

    public function test_http_permite_retirar_con_vacante_eliminada(): void
    {
        $this->vacante->delete();

        $this->retirarHttp($this->tokenHttp())
            ->assertOk()
            ->assertJsonPath('data.retirada', true);

        $this->assertNotNull(
            DB::table('empleos')
                ->where('id', $this->vacante->getKey())
                ->value('deleted_at')
        );
    }

    public function test_http_retiro_bloquea_repostulacion_y_actualiza_hu13(): void
    {
        $token = $this->tokenHttp();

        $this->retirarHttp($token)->assertOk();

        $this->reiniciarAutenticacionHttp();

        $this->postJson(
            '/api/v1/candidatos/me/postulaciones',
            ['vacante_id' => $this->vacante->getKey()],
            ['Authorization' => 'Bearer '.$token]
        )
            ->assertConflict()
            ->assertJsonPath(
                'error_code',
                'DUPLICATE_APPLICATION'
            );

        $this->reiniciarAutenticacionHttp();

        $this->getJson(
            '/api/v1/vacantes/'.$this->vacante->getKey(),
            ['Authorization' => 'Bearer '.$token]
        )
            ->assertOk()
            ->assertJsonPath('data.modo_consulta', 'disponible')
            ->assertJsonPath('data.postulacion.retirada', true)
            ->assertJsonPath('data.puede_postular', false)
            ->assertJsonPath(
                'data.motivo_no_postulable',
                'APPLICATION_WITHDRAWN'
            );

        $this->assertSame(
            1,
            DB::table('postulaciones')
                ->where('candidato_id', $this->candidato->getKey())
                ->where('empleo_id', $this->vacante->getKey())
                ->count()
        );
    }

    #[DataProvider('tokensInvalidosHttp')]
    public function test_http_token_revocado_o_expirado_no_retira(
        string $caso
    ): void {
        $token = $this->tokenHttp();

        $usuario = Usuario::findOrFail(
            $this->candidato->usuario_id
        );

        if ($caso === 'revocado') {
            $usuario->tokens()->delete();
        } else {
            $usuario->tokens()->update([
                'expires_at' => now()->subMinute(),
            ]);
        }

        $this->retirarHttp($token)
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');

        $this->assertNull(
            $this->registroPostulacion()['retirada_en']
        );
    }

    public static function tokensInvalidosHttp(): array
    {
        return [
            'revocado' => ['revocado'],
            'expirado' => ['expirado'],
        ];
    }

    private function tokenHttp(?int $usuarioId = null): string
    {
        return Usuario::findOrFail(
            $usuarioId ?? $this->candidato->usuario_id
        )
            ->createToken('prueba-hu16')
            ->plainTextToken;
    }

    private function rutaRetiroHttp(
        ?string $identificador = null
    ): string {
        return '/api/v1/candidatos/me/postulaciones/'
            .($identificador ?? $this->postulacion->getKey())
            .'/retirar';
    }

    private function reiniciarAutenticacionHttp(): void
    {
        $this->app->make('auth')->forgetGuards();

        $this->withoutHeader('Authorization');
    }

    private function retirarHttp(
        ?string $token = null,
        array $entrada = [],
        ?string $identificador = null
    ): TestResponse {
        $this->reiniciarAutenticacionHttp();

        $cabeceras = $token === null
            ? []
            : ['Authorization' => 'Bearer '.$token];

        return $this->patchJson(
            $this->rutaRetiroHttp($identificador),
            $entrada,
            $cabeceras
        );
    }

    private function retirar(): Postulacion
    {
        return app(RetiroPostulacionService::class)->retirar(
            $this->candidato,
            (int) $this->postulacion->getKey()
        );
    }

    private function registroPostulacion(): array
    {
        return (array) DB::table('postulaciones')
            ->where('id', $this->postulacion->getKey())
            ->first();
    }

    private function assertRechazo(
        callable $operacion,
        int $status,
        string $codigo
    ): void {
        try {
            $operacion();
        } catch (BusinessException $error) {
            $this->assertSame($status, $error->getStatusCode());
            $this->assertSame($codigo, $error->getErrorCode());

            return;
        }

        $this->fail(
            "La operación debía rechazarse con {$status} {$codigo}."
        );
    }

    private function cambiarEtapa(string $nombre): void
    {
        $estadoId = $this->estadoId($nombre);

        $this->postulacion->update([
            'estado_postulacion_id' => $estadoId,
        ]);

        DB::table('historial_postulaciones')->insert([
            'postulacion_id' => $this->postulacion->getKey(),
            'estado_postulacion_id' => $estadoId,
            'motivo_rechazo_id' => null,
            'fecha_cambio' => now()->subMinute(),
            'created_at' => now()->subMinute(),
            'updated_at' => now()->subMinute(),
        ]);
    }

    private function prepararDatos(): void
    {
        $this->candidato = $this->crearCandidato();
        $this->empresaUsuarioId = $this->crearUsuario('empresa');

        $empresaId = DB::table('empresas')->insertGetId([
            'usuario_id' => $this->empresaUsuarioId,
            'encargado_empresa' => 'Responsable HU16',
            'razon_social' => 'Empresa HU16 '.bin2hex(random_bytes(6)),
            'sector' => 'Tecnología e Informática',
            'descripcion' => 'Empresa para pruebas de retiro.',
            'ruc' => '20'.random_int(100000000, 999999999),
            'sitio_web' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $areaId = DB::table('areas_estudio')->insertGetId([
            'nombre' => 'Área HU16 '.bin2hex(random_bytes(6)),
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $nivelId = DB::table('niveles_puesto')
            ->whereNull('candidato_id')
            ->where('estado', 'activo')
            ->where('nombre', 'Junior')
            ->value('id');

        if ($nivelId === null) {
            throw new RuntimeException(
                'Falta el nivel global activo Junior.'
            );
        }

        $this->vacante = Empleo::create([
            'empresa_id' => $empresaId,
            'titulo' => 'Vacante para retiro HU16',
            'descripcion' => 'Vacante para comprobar el retiro y la conservación del historial de las postulaciones.',
            'nivel_puesto_id' => $nivelId,
            'area_estudio_id' => $areaId,
            'tipo_empleo' => 'remoto',
            'ubicacion' => null,
            'salario_min' => null,
            'salario_max' => null,
            'fecha_inicio_vigencia' => '2026-10-25',
            'fecha_fin_vigencia' => '2026-10-26',
            'estado_empleo' => Empleo::ACTIVA,
            'numero_vacantes' => 1,
        ]);

        DB::table('revisiones_vacante')->insert([
            'empleo_id' => $this->vacante->getKey(),
            'admin_id' => $this->crearUsuario('admin'),
            'decision' => 'aprobada',
            'motivo' => null,
            'invalidada_en' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pagos_vacante')->insert([
            'empleo_id' => $this->vacante->getKey(),
            'resultado' => 'exitoso',
            'monto' => 200,
            'moneda' => 'PEN',
            'tarjeta_ultimos4' => '1111',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->postulacion = Postulacion::create([
            'candidato_id' => $this->candidato->getKey(),
            'empleo_id' => $this->vacante->getKey(),
            'estado_postulacion_id' => $this->estadoId(
                EstadoPostulacion::RECIBIDO
            ),
            'fecha_postulacion' => now()->subHour(),
            'retirada_en' => null,
        ]);

        DB::table('historial_postulaciones')->insert([
            'postulacion_id' => $this->postulacion->getKey(),
            'estado_postulacion_id' => $this->postulacion
                ->estado_postulacion_id,
            'motivo_rechazo_id' => null,
            'fecha_cambio' => now()->subHour(),
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);
    }

    private function crearUsuario(string $rol): int
    {
        $rolId = DB::table('roles')
            ->where('nombre', $rol)
            ->value('id');

        if ($rolId === null) {
            $rolId = DB::table('roles')->insertGetId([
                'nombre' => $rol,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return (int) DB::table('usuarios')->insertGetId([
            'rol_id' => $rolId,
            'correo' => 'hu16-'.bin2hex(random_bytes(10)).'@example.test',
            'password' => Hash::make(bin2hex(random_bytes(16))),
            'estado_cuenta' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearCandidato(): Candidato
    {
        $usuarioId = $this->crearUsuario('candidato');

        return Candidato::create([
            'usuario_id' => $usuarioId,
            'nombre' => 'Candidato retiro',
            'apellidos' => 'Prueba HU16',
            'dni' => null,
            'fecha_nacimiento' => '2000-01-01',
            'genero' => null,
            'busca_primer_empleo' => true,
            'celular' => '9'.str_pad(
                (string) $usuarioId,
                8,
                '0',
                STR_PAD_LEFT
            ),
        ]);
    }

    private function estadoId(string $nombre): int
    {
        $id = DB::table('estados_postulacion')
            ->where('nombre', $nombre)
            ->value('id');

        if ($id === null) {
            $id = DB::table('estados_postulacion')->insertGetId([
                'nombre' => $nombre,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return (int) $id;
    }
}
