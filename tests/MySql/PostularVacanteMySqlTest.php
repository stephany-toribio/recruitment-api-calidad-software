<?php

namespace Tests\MySql;

use App\Exceptions\BusinessException;
use App\Models\Candidato;
use App\Models\Empleo;
use App\Models\EstadoPostulacion;
use App\Models\Postulacion;
use App\Models\Usuario;
use App\Services\Postulaciones\PostulacionService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class PostularVacanteMySqlTest extends TestCase
{
    private bool $transaccionIniciada = false;

    private Candidato $candidato;

    private Empleo $vacante;

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

    public function test_registra_una_postulacion_en_recibido(): void
    {
        $perfilAntes = (array) DB::table('candidatos')
            ->where('id', $this->candidato->getKey())
            ->first();

        $vacanteAntes = (array) DB::table('empleos')
            ->where('id', $this->vacante->getKey())
            ->first();

        $historialAntes = DB::table('historial_postulaciones')->count();
        $notificacionesAntes = DB::table('notificaciones_postulacion')->count();

        $postulacion = $this->postular();

        $this->assertTrue($postulacion->exists);
        $this->assertSame(
            (int) $this->candidato->getKey(),
            $postulacion->candidato_id
        );
        $this->assertSame(
            (int) $this->vacante->getKey(),
            $postulacion->empleo_id
        );
        $this->assertSame(
            EstadoPostulacion::RECIBIDO,
            $postulacion->estado->nombre
        );
        $this->assertSame(
            'recibido',
            $postulacion->estado->codigo()
        );
        $this->assertNull($postulacion->retirada_en);
        $this->assertTrue(
            $postulacion->fecha_postulacion->equalTo(now())
        );

        $registro = DB::table('postulaciones')
            ->where('id', $postulacion->getKey())
            ->first();

        $this->assertNotNull($registro);
        $this->assertNull($registro->porcentaje_match);
        $this->assertNull($registro->retirada_en);

        $this->assertSame(
            1,
            DB::table('postulaciones')
                ->where('candidato_id', $this->candidato->getKey())
                ->where('empleo_id', $this->vacante->getKey())
                ->count()
        );

        $this->assertTrue($postulacion->relationLoaded('estado'));
        $this->assertTrue($postulacion->relationLoaded('empleo'));

        $this->assertSame(
            $perfilAntes,
            (array) DB::table('candidatos')
                ->where('id', $this->candidato->getKey())
                ->first()
        );

        $this->assertSame(
            $vacanteAntes,
            (array) DB::table('empleos')
                ->where('id', $this->vacante->getKey())
                ->first()
        );

        $this->assertSame(
            $historialAntes,
            DB::table('historial_postulaciones')->count()
        );
        $this->assertSame(
            $notificacionesAntes,
            DB::table('notificaciones_postulacion')->count()
        );
    }

    #[DataProvider('postulacionesPrevias')]
    public function test_rechaza_duplicado_en_cualquier_etapa_o_retiro(
        string $estado,
        bool $retirada
    ): void {
        $previa = $this->crearPostulacionPrevia(
            $estado,
            $retirada
        );

        $antes = (array) DB::table('postulaciones')
            ->where('id', $previa->getKey())
            ->first();

        $this->assertRechazo(
            fn () => $this->postular(),
            409,
            'DUPLICATE_APPLICATION'
        );

        $this->assertSame(
            $antes,
            (array) DB::table('postulaciones')
                ->where('id', $previa->getKey())
                ->first()
        );

        $this->assertSame(
            1,
            DB::table('postulaciones')
                ->where('empleo_id', $this->vacante->getKey())
                ->count()
        );
    }

    public static function postulacionesPrevias(): array
    {
        return [
            'recibido' => [EstadoPostulacion::RECIBIDO, false],
            'en revision' => [EstadoPostulacion::EN_REVISION, false],
            'entrevista' => [EstadoPostulacion::ENTREVISTA, false],
            'contratado' => [EstadoPostulacion::CONTRATADO, false],
            'rechazado' => [EstadoPostulacion::RECHAZADO, false],
            'retirada' => [EstadoPostulacion::RECIBIDO, true],
        ];
    }

    public function test_dos_candidatos_distintos_pueden_postular(): void
    {
        $otroCandidato = $this->crearCandidato();

        $primera = $this->postular();

        $segunda = app(PostulacionService::class)->postular(
            $otroCandidato,
            (int) $this->vacante->getKey()
        );

        $this->assertNotSame(
            $primera->getKey(),
            $segunda->getKey()
        );
        $this->assertSame(
            (int) $otroCandidato->getKey(),
            $segunda->candidato_id
        );
        $this->assertSame(
            2,
            DB::table('postulaciones')
                ->where('empleo_id', $this->vacante->getKey())
                ->count()
        );
    }

    #[DataProvider('cambiosConPostulacionPrevia')]
    public function test_duplicado_prevalece_aunque_la_vacante_deje_de_estar_disponible(
        string $cambio
    ): void {
        $this->crearPostulacionPrevia(
            EstadoPostulacion::RECIBIDO,
            true
        );

        if ($cambio === 'eliminada') {
            $this->vacante->delete();
        } else {
            $this->vacante->update([
                'estado_empleo' => Empleo::PAUSADA,
            ]);
        }

        $this->assertRechazo(
            fn () => $this->postular(),
            409,
            'DUPLICATE_APPLICATION'
        );

        $this->assertSame(
            1,
            DB::table('postulaciones')
                ->where('empleo_id', $this->vacante->getKey())
                ->count()
        );
    }

    public static function cambiosConPostulacionPrevia(): array
    {
        return [
            'vacante pausada' => ['pausada'],
            'vacante eliminada' => ['eliminada'],
        ];
    }

    #[DataProvider('vacantesNoDisponibles')]
    public function test_rechaza_vacante_no_disponible_sin_crear_registros(
        string $caso
    ): void {
        $this->hacerNoDisponible($caso);

        $cantidadAntes = DB::table('postulaciones')->count();

        $this->assertRechazo(
            fn () => $this->postular(),
            404,
            'VACANCY_NOT_AVAILABLE'
        );

        $this->assertSame(
            $cantidadAntes,
            DB::table('postulaciones')->count()
        );
    }

    public static function vacantesNoDisponibles(): array
    {
        return [
            'pendiente de revision' => ['pendiente'],
            'no aprobada' => ['no_aprobada'],
            'revision invalidada' => ['revision_invalidada'],
            'sin pago' => ['sin_pago'],
            'solo pago fallido' => ['pago_fallido'],
            'solo pago cancelado' => ['pago_cancelado'],
            'pausada' => ['pausada'],
            'finalizada' => ['finalizada'],
            'eliminada' => ['eliminada'],
            'empresa suspendida' => ['empresa_suspendida'],
            'vigencia futura' => ['futura'],
            'vigencia vencida' => ['vencida'],
        ];
    }

    public function test_rechaza_vacante_inexistente(): void
    {
        $vacanteId = (int) $this->vacante->getKey();

        DB::table('pagos_vacante')
            ->where('empleo_id', $vacanteId)
            ->delete();

        DB::table('revisiones_vacante')
            ->where('empleo_id', $vacanteId)
            ->delete();

        DB::table('empleos')
            ->where('id', $vacanteId)
            ->delete();

        $cantidadAntes = DB::table('postulaciones')->count();

        $this->assertRechazo(
            fn () => app(PostulacionService::class)->postular(
                $this->candidato,
                $vacanteId
            ),
            404,
            'VACANCY_NOT_AVAILABLE'
        );

        $this->assertSame(
            $cantidadAntes,
            DB::table('postulaciones')->count()
        );
    }

    public function test_suspension_directa_del_candidato_bloquea_modelo_previamente_cargado(): void
    {
        DB::table('usuarios')
            ->where('id', $this->candidato->usuario_id)
            ->update(['estado_cuenta' => 'suspendido']);

        $this->assertRechazo(
            fn () => $this->postular(),
            403,
            'FORBIDDEN'
        );

        $this->assertDatabaseMissing('postulaciones', [
            'candidato_id' => $this->candidato->getKey(),
            'empleo_id' => $this->vacante->getKey(),
        ]);
    }

    public function test_rechaza_candidato_no_guardado(): void
    {
        $this->assertRechazo(
            fn () => app(PostulacionService::class)->postular(
                new Candidato,
                (int) $this->vacante->getKey()
            ),
            403,
            'FORBIDDEN'
        );
    }

    public function test_rechaza_perfil_eliminado_despues_de_cargarlo(): void
    {
        DB::table('candidatos')
            ->where('id', $this->candidato->getKey())
            ->delete();

        $this->assertRechazo(
            fn () => $this->postular(),
            403,
            'FORBIDDEN'
        );

        $this->assertDatabaseMissing('postulaciones', [
            'empleo_id' => $this->vacante->getKey(),
        ]);
    }

    public function test_http_registra_y_entrega_solo_los_campos_acordados(): void
    {
        $respuesta = $this->postularHttp(
            ['vacante_id' => $this->vacante->getKey()],
            $this->tokenHttp()
        );

        $respuesta
            ->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.estado.codigo', 'recibido')
            ->assertJsonPath('data.estado.nombre', 'Recibido')
            ->assertJsonPath('data.retirada', false)
            ->assertJsonPath(
                'data.vacante.id',
                (int) $this->vacante->getKey()
            )
            ->assertJsonPath(
                'data.vacante.titulo',
                $this->vacante->titulo
            );

        $datos = $respuesta->json('data');

        $this->assertSame([
            'id',
            'estado',
            'retirada',
            'fecha_postulacion',
            'vacante',
        ], array_keys($datos));

        $this->assertSame(
            ['codigo', 'nombre'],
            array_keys($datos['estado'])
        );

        $this->assertSame(
            ['id', 'titulo'],
            array_keys($datos['vacante'])
        );

        $this->assertTrue(
            Carbon::parse($datos['fecha_postulacion'])
                ->equalTo(now())
        );

        $this->assertDatabaseHas('postulaciones', [
            'id' => $datos['id'],
            'candidato_id' => $this->candidato->getKey(),
            'empleo_id' => $this->vacante->getKey(),
            'estado_postulacion_id' => $this->estadoId(
                EstadoPostulacion::RECIBIDO
            ),
            'retirada_en' => null,
            'porcentaje_match' => null,
        ]);
    }

    #[DataProvider('postulacionesPrevias')]
    public function test_http_duplicado_responde_409_sin_modificar_el_registro(
        string $estado,
        bool $retirada
    ): void {
        $previa = $this->crearPostulacionPrevia(
            $estado,
            $retirada
        );

        $antes = (array) DB::table('postulaciones')
            ->where('id', $previa->getKey())
            ->first();

        $this->postularHttp(
            ['vacante_id' => $this->vacante->getKey()],
            $this->tokenHttp()
        )
            ->assertConflict()
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'error_code',
                'DUPLICATE_APPLICATION'
            );

        $this->assertSame(
            $antes,
            (array) DB::table('postulaciones')
                ->where('id', $previa->getKey())
                ->first()
        );

        $this->assertSame(
            1,
            DB::table('postulaciones')
                ->where('empleo_id', $this->vacante->getKey())
                ->count()
        );
    }

    public function test_http_sin_token_responde_401_antes_de_validar(): void
    {
        $this->postularHttp([])
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');

        $this->assertSinPostulacionesHttp();
    }

    public function test_http_token_alterado_responde_401(): void
    {
        $this->postularHttp(
            ['vacante_id' => $this->vacante->getKey()],
            $this->tokenHttp().'alterado'
        )
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');

        $this->assertSinPostulacionesHttp();
    }

    #[DataProvider('rolesNoAutorizadosHttp')]
    public function test_http_otro_rol_responde_403(
        string $rol
    ): void {
        $token = $this->tokenHttp(
            $this->crearUsuario($rol)
        );

        $this->postularHttp(
            ['vacante_id' => $this->vacante->getKey()],
            $token
        )
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');

        $this->assertSinPostulacionesHttp();
    }

    public static function rolesNoAutorizadosHttp(): array
    {
        return [
            'empresa' => ['empresa'],
            'administrador' => ['admin'],
        ];
    }

    public function test_http_candidato_suspendido_no_registra(): void
    {
        $token = $this->tokenHttp();

        DB::table('usuarios')
            ->where('id', $this->candidato->usuario_id)
            ->update(['estado_cuenta' => 'suspendido']);

        $this->postularHttp(
            ['vacante_id' => $this->vacante->getKey()],
            $token
        )
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');

        $this->assertSinPostulacionesHttp();
    }

    public function test_http_candidato_sin_perfil_responde_403(): void
    {
        $token = $this->tokenHttp(
            $this->crearUsuario('candidato')
        );

        $this->postularHttp(
            ['vacante_id' => $this->vacante->getKey()],
            $token
        )
            ->assertForbidden()
            ->assertJsonPath('error_code', 'FORBIDDEN');

        $this->assertSinPostulacionesHttp();
    }

    #[DataProvider('entradasInvalidasHttp')]
    public function test_http_entrada_invalida_responde_422_sin_registrar(
        array $entrada,
        string $campo
    ): void {
        $this->postularHttp($entrada, $this->tokenHttp())
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors([$campo]);

        $this->assertSinPostulacionesHttp();
    }

    public static function entradasInvalidasHttp(): array
    {
        return [
            'vacante ausente' => [[], 'vacante_id'],
            'vacante nula' => [
                ['vacante_id' => null],
                'vacante_id',
            ],
            'vacante cero' => [
                ['vacante_id' => 0],
                'vacante_id',
            ],
            'vacante decimal' => [
                ['vacante_id' => 1.5],
                'vacante_id',
            ],
            'vacante booleana' => [
                ['vacante_id' => true],
                'vacante_id',
            ],
            'vacante como lista' => [
                ['vacante_id' => [1]],
                'vacante_id',
            ],
            'vacante fuera de rango' => [
                ['vacante_id' => '999999999999999999999999999999'],
                'vacante_id',
            ],
            'intenta imponer candidato' => [
                ['vacante_id' => 1, 'candidato_id' => 999],
                'candidato_id',
            ],
            'intenta imponer estado' => [
                ['vacante_id' => 1, 'estado_postulacion_id' => 999],
                'estado_postulacion_id',
            ],
            'envia expectativa salarial' => [
                ['vacante_id' => 1, 'expectativa_salarial' => 3000],
                'expectativa_salarial',
            ],
        ];
    }

    #[DataProvider('vacantesNoDisponibles')]
    public function test_http_vacante_no_disponible_responde_404(
        string $caso
    ): void {
        $this->hacerNoDisponible($caso);

        $this->postularHttp(
            ['vacante_id' => $this->vacante->getKey()],
            $this->tokenHttp()
        )
            ->assertNotFound()
            ->assertJsonPath('success', false)
            ->assertJsonPath(
                'error_code',
                'VACANCY_NOT_AVAILABLE'
            );

        $this->assertSinPostulacionesHttp();
    }

    public function test_http_postular_actualiza_detalle_y_conserva_historico_hu13(): void
    {
        $token = $this->tokenHttp();
        $rutaDetalle = '/api/v1/vacantes/'
            .$this->vacante->getKey();

        $this->reiniciarAutenticacionHttp();

        $this->getJson($rutaDetalle, [
            'Authorization' => 'Bearer '.$token,
        ])
            ->assertOk()
            ->assertJsonPath('data.puede_postular', true)
            ->assertJsonPath('data.postulacion', null);

        $creacion = $this->postularHttp(
            ['vacante_id' => $this->vacante->getKey()],
            $token
        );

        $creacion->assertCreated();
        $postulacionId = $creacion->json('data.id');

        $this->reiniciarAutenticacionHttp();

        $this->getJson($rutaDetalle, [
            'Authorization' => 'Bearer '.$token,
        ])
            ->assertOk()
            ->assertJsonPath('data.modo_consulta', 'disponible')
            ->assertJsonPath('data.puede_postular', false)
            ->assertJsonPath(
                'data.motivo_no_postulable',
                'ALREADY_APPLIED'
            )
            ->assertJsonPath(
                'data.postulacion.id',
                $postulacionId
            );

        $this->vacante->update([
            'estado_empleo' => Empleo::PAUSADA,
        ]);

        $this->reiniciarAutenticacionHttp();

        $this->getJson(
            '/api/v1/candidatos/me/postulaciones/'
                .$postulacionId.'/vacante',
            ['Authorization' => 'Bearer '.$token]
        )
            ->assertOk()
            ->assertJsonPath('data.modo_consulta', 'historico')
            ->assertJsonPath('data.puede_postular', false)
            ->assertJsonPath(
                'data.postulacion.id',
                $postulacionId
            );
    }

    #[DataProvider('tokensInvalidosHttp')]
    public function test_http_token_revocado_o_expirado_no_registra(
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

        $this->postularHttp(
            ['vacante_id' => $this->vacante->getKey()],
            $token
        )
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');

        $this->assertSinPostulacionesHttp();
    }

    public static function tokensInvalidosHttp(): array
    {
        return [
            'revocado' => ['revocado'],
            'expirado' => ['expirado'],
        ];
    }

    public function test_http_error_interno_no_expone_detalles(): void
    {
        $token = $this->tokenHttp();

        $this->mock(
            PostulacionService::class,
            function ($mock): void {
                $mock->shouldReceive('postular')
                    ->once()
                    ->andThrow(
                        new RuntimeException(
                            'HU05_DETALLE_INTERNO_NO_PUBLICAR'
                        )
                    );
            }
        );

        $respuesta = $this->postularHttp(
            ['vacante_id' => $this->vacante->getKey()],
            $token
        );

        $respuesta
            ->assertStatus(500)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'SERVER_ERROR');

        $this->assertStringNotContainsString(
            'HU05_DETALLE_INTERNO_NO_PUBLICAR',
            $respuesta->getContent()
        );

        $this->assertArrayNotHasKey(
            'trace',
            $respuesta->json()
        );
        $this->assertArrayNotHasKey(
            'exception',
            $respuesta->json()
        );

        $this->assertSinPostulacionesHttp();
    }

    private function tokenHttp(?int $usuarioId = null): string
    {
        return Usuario::findOrFail(
            $usuarioId ?? $this->candidato->usuario_id
        )
            ->createToken('prueba-hu05')
            ->plainTextToken;
    }

    private function reiniciarAutenticacionHttp(): void
    {
        $this->app->make('auth')->forgetGuards();

        $this->withoutHeader('Authorization');
    }

    private function postularHttp(
        array $entrada,
        ?string $token = null
    ): TestResponse {
        $this->reiniciarAutenticacionHttp();

        $cabeceras = $token === null
            ? []
            : ['Authorization' => 'Bearer '.$token];

        return $this->postJson(
            '/api/v1/candidatos/me/postulaciones',
            $entrada,
            $cabeceras
        );
    }

    private function assertSinPostulacionesHttp(): void
    {
        $this->assertDatabaseMissing('postulaciones', [
            'empleo_id' => $this->vacante->getKey(),
        ]);
    }

    public function test_fallo_despues_del_insert_revierte_la_postulacion(): void
    {
        $cantidadAntes = DB::table('postulaciones')->count();
        $nivelTransaccionAntes = DB::transactionLevel();

        $insertObservado = false;
        $falloObservado = false;

        $dispatcherOriginal = Postulacion::getEventDispatcher();

        if ($dispatcherOriginal === null) {
            throw new RuntimeException(
                'Falta el dispatcher de eventos de Eloquent.'
            );
        }

        $dispatcherTemporal = clone $dispatcherOriginal;

        $dispatcherTemporal->listen(
            'eloquent.created: '.Postulacion::class,
            function (Postulacion $postulacion) use (
                &$insertObservado
            ): void {
                $insertObservado = DB::table('postulaciones')
                    ->where('id', $postulacion->getKey())
                    ->where(
                        'candidato_id',
                        $this->candidato->getKey()
                    )
                    ->where(
                        'empleo_id',
                        $this->vacante->getKey()
                    )
                    ->exists();

                throw new RuntimeException(
                    'HU05_FALLO_CONTROLADO_DESPUES_DEL_INSERT'
                );
            }
        );

        Postulacion::setEventDispatcher($dispatcherTemporal);

        try {
            $this->postular();
        } catch (RuntimeException $error) {
            if (
                $error->getMessage()
                !== 'HU05_FALLO_CONTROLADO_DESPUES_DEL_INSERT'
            ) {
                throw $error;
            }

            $falloObservado = true;
        } finally {
            Postulacion::setEventDispatcher(
                $dispatcherOriginal
            );
        }

        $this->assertTrue(
            $insertObservado,
            'La prueba debe alcanzar un INSERT real antes del fallo.'
        );

        $this->assertTrue(
            $falloObservado,
            'El fallo controlado debe propagarse al llamador.'
        );

        $this->assertSame(
            $cantidadAntes,
            DB::table('postulaciones')->count()
        );

        $this->assertDatabaseMissing('postulaciones', [
            'candidato_id' => $this->candidato->getKey(),
            'empleo_id' => $this->vacante->getKey(),
        ]);

        $this->assertSame(
            $nivelTransaccionAntes,
            DB::transactionLevel(),
            'El servicio debe cerrar su transacción sin cerrar la del test.'
        );
    }

    private function postular(): Postulacion
    {
        return app(PostulacionService::class)->postular(
            $this->candidato,
            (int) $this->vacante->getKey()
        );
    }

    private function assertRechazo(
        callable $operacion,
        int $estado,
        string $codigo
    ): void {
        try {
            $operacion();
        } catch (BusinessException $error) {
            $this->assertSame($estado, $error->getStatusCode());
            $this->assertSame($codigo, $error->getErrorCode());

            return;
        }

        $this->fail(
            "La operación debía rechazarse con {$estado} {$codigo}."
        );
    }

    private function hacerNoDisponible(string $caso): void
    {
        $vacanteId = $this->vacante->getKey();

        switch ($caso) {
            case 'pendiente':
                DB::table('revisiones_vacante')
                    ->where('empleo_id', $vacanteId)
                    ->delete();
                break;

            case 'no_aprobada':
                DB::table('revisiones_vacante')
                    ->where('empleo_id', $vacanteId)
                    ->update([
                        'decision' => 'no_aprobada',
                        'motivo' => 'No cumple los requisitos de publicación.',
                    ]);
                break;

            case 'revision_invalidada':
                DB::table('revisiones_vacante')
                    ->where('empleo_id', $vacanteId)
                    ->update(['invalidada_en' => now()]);
                break;

            case 'sin_pago':
                DB::table('pagos_vacante')
                    ->where('empleo_id', $vacanteId)
                    ->delete();
                break;

            case 'pago_fallido':
                DB::table('pagos_vacante')
                    ->where('empleo_id', $vacanteId)
                    ->update(['resultado' => 'fallido']);
                break;

            case 'pago_cancelado':
                DB::table('pagos_vacante')
                    ->where('empleo_id', $vacanteId)
                    ->update([
                        'resultado' => 'cancelado',
                        'tarjeta_ultimos4' => null,
                    ]);
                break;

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

            case 'empresa_suspendida':
                DB::table('usuarios')
                    ->where('id', $this->empresaUsuarioId)
                    ->update(['estado_cuenta' => 'suspendido']);
                break;

            case 'futura':
                $this->vacante->update([
                    'fecha_inicio_vigencia' => '2026-10-26',
                    'fecha_fin_vigencia' => '2026-10-27',
                ]);
                break;

            case 'vencida':
                $this->vacante->update([
                    'fecha_inicio_vigencia' => '2026-10-23',
                    'fecha_fin_vigencia' => '2026-10-24',
                ]);
                break;

            default:
                throw new RuntimeException(
                    "Caso de prueba desconocido: {$caso}."
                );
        }
    }

    private function prepararDatos(): void
    {
        $this->candidato = $this->crearCandidato();

        $this->empresaUsuarioId = $this->crearUsuario('empresa');

        $empresaId = DB::table('empresas')->insertGetId([
            'usuario_id' => $this->empresaUsuarioId,
            'encargado_empresa' => 'Responsable HU05',
            'razon_social' => 'Empresa HU05 '.bin2hex(random_bytes(6)),
            'sector' => 'Tecnología e Informática',
            'descripcion' => 'Empresa para pruebas de postulación.',
            'ruc' => '20'.random_int(100000000, 999999999),
            'sitio_web' => null,
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

        $areaId = DB::table('areas_estudio')->insertGetId([
            'nombre' => 'Área HU05 '.bin2hex(random_bytes(6)),
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->vacante = Empleo::create([
            'empresa_id' => $empresaId,
            'titulo' => 'Analista de soluciones HU05',
            'descripcion' => 'Buscamos una persona para desarrollar soluciones y documentar los resultados del equipo.',
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

        $this->estadoId(EstadoPostulacion::RECIBIDO);
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
            'correo' => 'hu05-'.bin2hex(random_bytes(10)).'@example.test',
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
            'nombre' => 'Candidato de prueba',
            'apellidos' => 'Postulación HU05',
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

    private function crearPostulacionPrevia(
        string $estado,
        bool $retirada
    ): Postulacion {
        return Postulacion::create([
            'candidato_id' => $this->candidato->getKey(),
            'empleo_id' => $this->vacante->getKey(),
            'estado_postulacion_id' => $this->estadoId($estado),
            'fecha_postulacion' => now()->subHour(),
            'retirada_en' => $retirada ? now() : null,
        ]);
    }
}
