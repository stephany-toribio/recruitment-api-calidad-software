<?php

namespace Tests\MySql;

use App\Http\Resources\Api\V1\VacanteDetalleResource;
use App\Models\Candidato;
use App\Models\Empleo;
use App\Models\Postulacion;
use App\Models\Usuario;
use App\Services\Vacantes\DetalleVacanteService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

class DetalleVacanteMySqlTest extends TestCase
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

    public function test_obtiene_detalle_disponible_sin_postulacion(): void
    {
        $detalle = $this->consultar();

        $this->assertSame(
            (int) $this->vacante->getKey(),
            (int) $detalle['vacante']->getKey()
        );

        $this->assertSame('disponible', $detalle['modo_consulta']);
        $this->assertNull($detalle['postulacion']);
        $this->assertTrue($detalle['puede_postular']);
        $this->assertNull($detalle['motivo_no_postulable']);
    }

    public function test_postulacion_propia_impide_postular_nuevamente(): void
    {
        $postulacion = $this->crearPostulacion($this->candidato);

        $detalle = $this->consultar();

        $this->assertSame('disponible', $detalle['modo_consulta']);
        $this->assertSame(
            (int) $postulacion->getKey(),
            (int) $detalle['postulacion']->getKey()
        );
        $this->assertFalse($detalle['puede_postular']);
        $this->assertSame(
            'ALREADY_APPLIED',
            $detalle['motivo_no_postulable']
        );
    }

    public function test_postulacion_retirada_tambien_impide_postular(): void
    {
        $this->crearPostulacion($this->candidato, true);

        $detalle = $this->consultar();
        $respuesta = $this->resolverRecurso($detalle);

        $this->assertSame('disponible', $detalle['modo_consulta']);
        $this->assertFalse($detalle['puede_postular']);
        $this->assertSame(
            'APPLICATION_WITHDRAWN',
            $detalle['motivo_no_postulable']
        );
        $this->assertTrue($respuesta['postulacion']['retirada']);
    }

    public function test_postulacion_ajena_no_se_expone_ni_bloquea_al_candidato(): void
    {
        $otroCandidato = $this->crearCandidato();

        $this->crearPostulacion($otroCandidato);

        $detalle = $this->consultar();
        $respuesta = $this->resolverRecurso($detalle);

        $this->assertNull($detalle['postulacion']);
        $this->assertNull($respuesta['postulacion']);
        $this->assertTrue($respuesta['puede_postular']);
        $this->assertNull($respuesta['motivo_no_postulable']);

        $this->assertSame(
            1,
            (int) $detalle['vacante']->postulaciones_count
        );
        $this->assertSame(
            'Menos de 10 postulantes',
            $respuesta['postulantes_aprox']
        );
        $this->assertArrayNotHasKey(
            'postulaciones_count',
            $respuesta
        );
    }

    public function test_recurso_entrega_solo_los_campos_publicos_acordados(): void
    {
        $respuesta = $this->resolverRecurso($this->consultar());

        $esperadas = [
            'id',
            'titulo',
            'descripcion',
            'requisitos',
            'beneficios',
            'empresa',
            'nivel_puesto',
            'area_profesional',
            'tipo_empleo',
            'ubicacion',
            'salario_min',
            'salario_max',
            'fecha_inicio_vigencia',
            'fecha_fin_vigencia',
            'publicada_en',
            'habilidades',
            'postulantes_aprox',
            'modo_consulta',
            'estado',
            'postulacion',
            'puede_postular',
            'motivo_no_postulable',
        ];

        $actuales = array_keys($respuesta);

        sort($esperadas);
        sort($actuales);

        $this->assertSame($esperadas, $actuales);

        $this->assertCount(22, $respuesta);
        $this->assertIsArray($respuesta['requisitos']);
        $this->assertIsArray($respuesta['beneficios']);
        $this->assertSame([], $respuesta['requisitos']);
        $this->assertSame([], $respuesta['beneficios']);


        $this->assertSame(
            [
                'razon_social',
                'sector',
                'descripcion',
            ],
            array_keys($respuesta['empresa'])
        );

        $this->assertSame(
            'Empresa dedicada a desarrollar soluciones digitales.',
            $respuesta['empresa']['descripcion']
        );

        $this->assertCount(2, $respuesta['habilidades']);

        foreach ($respuesta['habilidades'] as $habilidad) {
            $this->assertSame(
                ['id', 'nombre'],
                array_keys($habilidad)
            );
        }

        $this->assertSame(
            "**Responsabilidades**\n\n- Desarrollar soluciones.\n- Documentar resultados.",
            $respuesta['descripcion']
        );

        $this->assertSame(
            '2026-10-25',
            $respuesta['fecha_inicio_vigencia']
        );
        $this->assertSame(
            '2026-10-26',
            $respuesta['fecha_fin_vigencia']
        );
        $this->assertNotNull($respuesta['publicada_en']);

        foreach ([
            'numero_vacantes',
            'max_contrataciones',
            'nivel_puesto_dueno_id',
            'pagos',
            'revision',
            'postulaciones',
            'porcentaje_match',
        ] as $campo) {
            $this->assertArrayNotHasKey($campo, $respuesta);
        }
    }


    public function test_detalle_publico_conserva_requisitos_y_beneficios(): void
    {
        $requisitos = [
            'Conocimientos de SQL',
            'Capacidad de documentacion',
        ];

        $beneficios = [
            'Capacitacion continua',
            'Horario flexible',
        ];

        DB::table('empleos')
            ->where('id', $this->vacante->getKey())
            ->update([
                'requisitos' => json_encode(
                    $requisitos,
                    JSON_THROW_ON_ERROR
                ),
                'beneficios' => json_encode(
                    $beneficios,
                    JSON_THROW_ON_ERROR
                ),
            ]);

        $respuesta = $this->resolverRecurso(
            $this->consultar()
        );

        $this->assertSame(
            $requisitos,
            $respuesta['requisitos']
        );

        $this->assertSame(
            $beneficios,
            $respuesta['beneficios']
        );

        foreach ($respuesta['requisitos'] as $requisito) {
            $this->assertIsString($requisito);
        }

        foreach ($respuesta['beneficios'] as $beneficio) {
            $this->assertIsString($beneficio);
        }

        $this->assertArrayNotHasKey(
            'postulaciones_count',
            $respuesta
        );

        $this->assertArrayNotHasKey(
            'porcentaje_match',
            $respuesta
        );
    }

    public function test_detalle_http_expone_requisitos_y_beneficios_publicos(): void
    {
        $requisitos = ['Conocimientos de Python'];
        $beneficios = ['Trabajo remoto'];

        DB::table('empleos')
            ->where('id', $this->vacante->getKey())
            ->update([
                'requisitos' => json_encode(
                    $requisitos,
                    JSON_THROW_ON_ERROR
                ),
                'beneficios' => json_encode(
                    $beneficios,
                    JSON_THROW_ON_ERROR
                ),
            ]);

        $token = $this->crearTokenHttp(
            (int) $this->candidato->usuario_id
        );

        $this->consultarHttp(
            '/api/v1/vacantes/'.$this->vacante->getKey(),
            $token
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.requisitos', $requisitos)
            ->assertJsonPath('data.beneficios', $beneficios)
            ->assertJsonMissingPath('data.postulaciones_count')
            ->assertJsonMissingPath('data.porcentaje_match')
            ->assertJsonMissingPath('data.pagos')
            ->assertJsonMissingPath('data.revision');
    }

    public function test_conserva_salarios_ausentes_y_normaliza_descripcion_empresarial_vacia(): void
    {
        DB::table('empresas')
            ->where('id', $this->vacante->empresa_id)
            ->update(['descripcion' => '   ']);

        $respuesta = $this->resolverRecurso($this->consultar());

        $this->assertNull($respuesta['salario_min']);
        $this->assertNull($respuesta['salario_max']);
        $this->assertNull($respuesta['empresa']['descripcion']);
    }

    public function test_vacante_pausada_se_consulta_en_modo_historico_sin_postular(): void
    {
        $this->vacante->update([
            'estado_empleo' => Empleo::PAUSADA,
        ]);

        $respuesta = $this->resolverRecurso($this->consultar());

        $this->assertSame('historico', $respuesta['modo_consulta']);
        $this->assertSame(Empleo::PAUSADA, $respuesta['estado']);
        $this->assertFalse($respuesta['puede_postular']);
        $this->assertSame(
            'VACANCY_UNAVAILABLE',
            $respuesta['motivo_no_postulable']
        );
        $this->assertNull($respuesta['postulacion']);
    }

    public function test_vacante_finalizada_o_eliminada_se_ve_por_url_con_su_estado(): void
    {
        $this->vacante->update([
            'estado_empleo' => Empleo::FINALIZADA,
        ]);

        $respuesta = $this->resolverRecurso($this->consultar());

        $this->assertSame(Empleo::FINALIZADA, $respuesta['estado']);
        $this->assertSame('historico', $respuesta['modo_consulta']);

        $this->vacante->delete();

        $respuesta = $this->resolverRecurso($this->consultar());

        $this->assertSame(Empleo::ELIMINADA, $respuesta['estado']);
        $this->assertSame('historico', $respuesta['modo_consulta']);
        $this->assertSame($this->vacante->descripcion, $respuesta['descripcion']);
    }

    public function test_vacante_nunca_publicada_sigue_oculta_aunque_este_finalizada(): void
    {
        DB::table('pagos_vacante')
            ->where('empleo_id', $this->vacante->getKey())
            ->delete();

        $this->vacante->update([
            'estado_empleo' => Empleo::FINALIZADA,
        ]);

        $this->expectException(ModelNotFoundException::class);

        $this->consultar();
    }

    public function test_suspension_de_empresa_bloquea_el_detalle(): void
    {
        DB::table('usuarios')
            ->where('id', $this->empresaUsuarioId)
            ->update(['estado_cuenta' => 'suspendido']);

        $this->expectException(ModelNotFoundException::class);

        $this->consultar();
    }

    public function test_revision_invalidada_no_habilita_el_detalle(): void
    {
        DB::table('revisiones_vacante')
            ->where('empleo_id', $this->vacante->getKey())
            ->update(['invalidada_en' => now()]);

        $this->expectException(ModelNotFoundException::class);

        $this->consultar();
    }

    public function test_historico_permite_consultar_vacante_pausada(): void
    {
        $postulacion = $this->crearPostulacion($this->candidato);

        $this->vacante->update([
            'estado_empleo' => Empleo::PAUSADA,
        ]);

        $detalle = app(DetalleVacanteService::class)->historico(
            (int) $postulacion->getKey(),
            $this->candidato
        );

        $respuesta = $this->resolverRecurso($detalle);

        $this->assertSame('historico', $respuesta['modo_consulta']);
        $this->assertFalse($respuesta['puede_postular']);
        $this->assertSame(
            'VACANCY_UNAVAILABLE',
            $respuesta['motivo_no_postulable']
        );
        $this->assertSame(
            (int) $postulacion->getKey(),
            $respuesta['postulacion']['id']
        );
        $this->assertFalse($respuesta['postulacion']['retirada']);
        $this->assertSame(
            (int) $this->vacante->getKey(),
            $respuesta['id']
        );
    }

    public function test_historico_permite_consultar_vacante_finalizada(): void
    {
        $postulacion = $this->crearPostulacion($this->candidato);

        $this->vacante->update([
            'estado_empleo' => Empleo::FINALIZADA,
        ]);

        $detalle = app(DetalleVacanteService::class)->historico(
            (int) $postulacion->getKey(),
            $this->candidato
        );

        $this->assertSame('historico', $detalle['modo_consulta']);
        $this->assertFalse($detalle['puede_postular']);
        $this->assertSame(
            (int) $this->vacante->getKey(),
            (int) $detalle['vacante']->getKey()
        );
    }

    public function test_historico_conserva_acceso_con_postulacion_retirada(): void
    {
        $postulacion = $this->crearPostulacion(
            $this->candidato,
            true
        );

        $this->vacante->update([
            'estado_empleo' => Empleo::PAUSADA,
        ]);

        $detalle = app(DetalleVacanteService::class)->historico(
            (int) $postulacion->getKey(),
            $this->candidato
        );

        $respuesta = $this->resolverRecurso($detalle);

        $this->assertSame('historico', $respuesta['modo_consulta']);
        $this->assertTrue($respuesta['postulacion']['retirada']);
        $this->assertFalse($respuesta['puede_postular']);
        $this->assertSame(
            'VACANCY_UNAVAILABLE',
            $respuesta['motivo_no_postulable']
        );
    }

    public function test_historico_rechaza_postulacion_ajena(): void
    {
        $otroCandidato = $this->crearCandidato();

        $postulacionAjena = $this->crearPostulacion(
            $otroCandidato
        );

        $this->vacante->update([
            'estado_empleo' => Empleo::PAUSADA,
        ]);

        $this->expectException(ModelNotFoundException::class);

        app(DetalleVacanteService::class)->historico(
            (int) $postulacionAjena->getKey(),
            $this->candidato
        );
    }

    public function test_historico_rechaza_postulacion_inexistente(): void
    {
        $postulacion = $this->crearPostulacion($this->candidato);
        $postulacionId = (int) $postulacion->getKey();

        $postulacion->delete();

        $this->expectException(ModelNotFoundException::class);

        app(DetalleVacanteService::class)->historico(
            $postulacionId,
            $this->candidato
        );
    }

    public function test_vacante_activa_no_se_consulta_como_historico(): void
    {
        $postulacion = $this->crearPostulacion($this->candidato);

        $this->expectException(ModelNotFoundException::class);

        app(DetalleVacanteService::class)->historico(
            (int) $postulacion->getKey(),
            $this->candidato
        );
    }

    public function test_vacante_activa_vencida_no_se_convierte_en_historico(): void
    {
        $postulacion = $this->crearPostulacion($this->candidato);

        $this->vacante->update([
            'fecha_inicio_vigencia' => '2026-10-23',
            'fecha_fin_vigencia' => '2026-10-24',
        ]);

        $this->expectException(ModelNotFoundException::class);

        app(DetalleVacanteService::class)->historico(
            (int) $postulacion->getKey(),
            $this->candidato
        );
    }

    public function test_historico_permite_consultar_vacante_eliminada(): void
    {
        $postulacion = $this->crearPostulacion($this->candidato);

        $this->vacante->update([
            'estado_empleo' => Empleo::PAUSADA,
        ]);

        $this->vacante->delete();

        $this->assertDatabaseHas('empleos', [
            'id' => $this->vacante->getKey(),
        ]);

        $this->assertNotNull(
            DB::table('empleos')
                ->where('id', $this->vacante->getKey())
                ->value('deleted_at')
        );

        $respuesta = $this->resolverRecurso(
            app(DetalleVacanteService::class)->historico(
                (int) $postulacion->getKey(),
                $this->candidato
            )
        );

        $this->assertSame('historico', $respuesta['modo_consulta']);
        $this->assertSame(Empleo::ELIMINADA, $respuesta['estado']);
        $this->assertSame(
            (int) $postulacion->getKey(),
            $respuesta['postulacion']['id']
        );
    }

    public function test_historico_rechaza_empresa_suspendida(): void
    {
        $postulacion = $this->crearPostulacion($this->candidato);

        $this->vacante->update([
            'estado_empleo' => Empleo::FINALIZADA,
        ]);

        DB::table('usuarios')
            ->where('id', $this->empresaUsuarioId)
            ->update(['estado_cuenta' => 'suspendido']);

        $this->expectException(ModelNotFoundException::class);

        app(DetalleVacanteService::class)->historico(
            (int) $postulacion->getKey(),
            $this->candidato
        );
    }

    public function test_historico_muestra_contenido_actual_sin_modificar_postulacion(): void
    {
        $postulacion = $this->crearPostulacion($this->candidato);

        $registroAnterior = (array) DB::table('postulaciones')
            ->where('id', $postulacion->getKey())
            ->first();

        $this->vacante->update([
            'estado_empleo' => Empleo::PAUSADA,
            'titulo' => 'Título actualizado para consulta histórica',
            'descripcion' => 'Contenido actual de la vacante. Esta consulta no representa una copia de la oferta original.',
        ]);

        $detalle = app(DetalleVacanteService::class)->historico(
            (int) $postulacion->getKey(),
            $this->candidato
        );

        $respuesta = $this->resolverRecurso($detalle);

        $this->assertSame(
            'Título actualizado para consulta histórica',
            $respuesta['titulo']
        );

        $this->assertSame(
            $this->vacante->descripcion,
            $respuesta['descripcion']
        );

        $this->assertSame(
            $registroAnterior,
            (array) DB::table('postulaciones')
                ->where('id', $postulacion->getKey())
                ->first()
        );

        $this->assertFalse($respuesta['puede_postular']);
    }

    public function test_historico_conserva_habilidades_desactivadas_sin_exponer_clasificaciones(): void
    {
        $postulacion = $this->crearPostulacion($this->candidato);

        $habilidadesIds = $this->vacante->habilidades()
            ->pluck('habilidades.id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        DB::table('habilidades')
            ->whereIn('id', $habilidadesIds)
            ->update(['activo' => false]);

        $this->vacante->update([
            'estado_empleo' => Empleo::FINALIZADA,
        ]);

        $detalle = app(DetalleVacanteService::class)->historico(
            (int) $postulacion->getKey(),
            $this->candidato
        );

        $respuesta = $this->resolverRecurso($detalle);

        $idsRecibidos = array_column(
            $respuesta['habilidades'],
            'id'
        );

        sort($habilidadesIds);
        sort($idsRecibidos);

        $this->assertSame($habilidadesIds, $idsRecibidos);
        $this->assertCount(2, $respuesta['habilidades']);

        foreach ($respuesta['habilidades'] as $habilidad) {
            $this->assertSame(
                ['id', 'nombre'],
                array_keys($habilidad)
            );
        }
    }

    public function test_http_candidato_consulta_detalle_disponible(): void
    {
        $token = $this->crearTokenHttp(
            (int) $this->candidato->usuario_id
        );

        $respuesta = $this->consultarHttp(
            '/api/v1/vacantes/'.$this->vacante->getKey(),
            $token
        );

        $respuesta
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath(
                'data.id',
                (int) $this->vacante->getKey()
            )
            ->assertJsonPath('data.modo_consulta', 'disponible')
            ->assertJsonPath('data.puede_postular', true)
            ->assertJsonPath('data.postulacion', null)
            ->assertJsonPath('data.motivo_no_postulable', null)
            ->assertJsonPath('data.salario_min', null)
            ->assertJsonPath('data.salario_max', null);

        $datos = $respuesta->json('data');

        $this->assertArrayNotHasKey('numero_vacantes', $datos);
        $this->assertArrayNotHasKey('max_contrataciones', $datos);
        $this->assertArrayNotHasKey('postulaciones_count', $datos);

        $this->assertSame(
            ['razon_social', 'sector', 'descripcion'],
            array_keys($datos['empresa'])
        );

        foreach ($datos['habilidades'] as $habilidad) {
            $this->assertSame(
                ['id', 'nombre'],
                array_keys($habilidad)
            );
        }
    }

    public function test_http_candidato_consulta_historico_propio(): void
    {
        $postulacion = $this->crearPostulacion(
            $this->candidato,
            true
        );

        $this->vacante->update([
            'estado_empleo' => Empleo::FINALIZADA,
        ]);

        $token = $this->crearTokenHttp(
            (int) $this->candidato->usuario_id
        );

        $this->consultarHttp(
            '/api/v1/candidatos/me/postulaciones/'
                .$postulacion->getKey().'/vacante',
            $token
        )
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.modo_consulta', 'historico')
            ->assertJsonPath('data.puede_postular', false)
            ->assertJsonPath(
                'data.motivo_no_postulable',
                'VACANCY_UNAVAILABLE'
            )
            ->assertJsonPath(
                'data.postulacion.id',
                (int) $postulacion->getKey()
            )
            ->assertJsonPath('data.postulacion.retirada', true)
            ->assertJsonMissingPath('data.habilidades.0.requisito');
    }

    public function test_http_ambos_endpoints_rechazan_solicitudes_sin_token(): void
    {
        foreach ($this->rutasDetalleHttp() as $ruta) {
            $this->consultarHttp($ruta)
                ->assertUnauthorized()
                ->assertJsonPath('success', false);
        }
    }

    public function test_http_ambos_endpoints_rechazan_token_alterado(): void
    {
        $token = $this->crearTokenHttp(
            (int) $this->candidato->usuario_id
        );

        foreach ($this->rutasDetalleHttp() as $ruta) {
            $this->consultarHttp($ruta, $token.'alterado')
                ->assertUnauthorized()
                ->assertJsonPath('success', false);
        }
    }

    public function test_http_otros_roles_no_acceden_a_ningun_detalle(): void
    {
        $rutas = $this->rutasDetalleHttp();

        foreach (['empresa', 'admin'] as $rol) {
            $token = $this->crearTokenHttp(
                $this->crearUsuario($rol)
            );

            foreach ($rutas as $ruta) {
                $this->consultarHttp($ruta, $token)
                    ->assertForbidden()
                    ->assertJsonPath('success', false)
                    ->assertJsonPath('error_code', 'FORBIDDEN');
            }
        }
    }

    public function test_http_suspension_directa_bloquea_token_existente(): void
    {
        $token = $this->crearTokenHttp(
            (int) $this->candidato->usuario_id
        );

        DB::table('usuarios')
            ->where('id', $this->candidato->usuario_id)
            ->update(['estado_cuenta' => 'suspendido']);

        foreach ($this->rutasDetalleHttp() as $ruta) {
            $this->consultarHttp($ruta, $token)
                ->assertForbidden()
                ->assertJsonPath('success', false);
        }
    }

    public function test_http_postulacion_ajena_e_inexistente_devuelven_el_mismo_error(): void
    {
        $otroCandidato = $this->crearCandidato();

        $postulacion = $this->crearPostulacion($otroCandidato);

        $this->vacante->update([
            'estado_empleo' => Empleo::PAUSADA,
        ]);

        $token = $this->crearTokenHttp(
            (int) $this->candidato->usuario_id
        );

        $ruta = '/api/v1/candidatos/me/postulaciones/'
            .$postulacion->getKey().'/vacante';

        $ajena = $this->consultarHttp($ruta, $token);

        $ajena
            ->assertNotFound()
            ->assertJsonPath('success', false);

        $postulacion->delete();

        $inexistente = $this->consultarHttp($ruta, $token);

        $inexistente
            ->assertNotFound()
            ->assertJsonPath('success', false);

        $this->assertSame(
            $ajena->json(),
            $inexistente->json()
        );

        $this->assertArrayNotHasKey('data', $ajena->json());
        $this->assertArrayNotHasKey('exception', $ajena->json());
        $this->assertArrayNotHasKey('trace', $ajena->json());
    }

    public function test_http_vacante_pausada_se_ve_por_url_y_por_postulacion(): void
    {
        $postulacion = $this->crearPostulacion($this->candidato);

        $this->vacante->update([
            'estado_empleo' => Empleo::PAUSADA,
        ]);

        $token = $this->crearTokenHttp(
            (int) $this->candidato->usuario_id
        );

        $this->consultarHttp(
            '/api/v1/vacantes/'.$this->vacante->getKey(),
            $token
        )
            ->assertOk()
            ->assertJsonPath('data.modo_consulta', 'historico')
            ->assertJsonPath('data.estado', Empleo::PAUSADA)
            ->assertJsonPath('data.puede_postular', false);

        $this->consultarHttp(
            '/api/v1/candidatos/me/postulaciones/'
                .$postulacion->getKey().'/vacante',
            $token
        )
            ->assertOk()
            ->assertJsonPath('data.modo_consulta', 'historico')
            ->assertJsonPath('data.puede_postular', false);
    }

    public function test_http_usuario_candidato_sin_perfil_recibe_403(): void
    {
        $token = $this->crearTokenHttp(
            $this->crearUsuario('candidato')
        );

        foreach ($this->rutasDetalleHttp() as $ruta) {
            $this->consultarHttp($ruta, $token)
                ->assertForbidden()
                ->assertJsonPath('success', false)
                ->assertJsonPath('error_code', 'FORBIDDEN');
        }
    }

    public function test_http_token_revocado_no_accede_a_ningun_detalle(): void
    {
        $usuarioId = (int) $this->candidato->usuario_id;

        $token = $this->crearTokenHttp($usuarioId);

        Usuario::findOrFail($usuarioId)
            ->tokens()
            ->delete();

        foreach ($this->rutasDetalleHttp() as $ruta) {
            $this->consultarHttp($ruta, $token)
                ->assertUnauthorized()
                ->assertJsonPath('success', false);
        }
    }

    public function test_http_token_expirado_no_accede_a_ningun_detalle(): void
    {
        $usuarioId = (int) $this->candidato->usuario_id;

        $token = $this->crearTokenHttp($usuarioId);

        Usuario::findOrFail($usuarioId)
            ->tokens()
            ->update([
                'expires_at' => now()->subMinute(),
            ]);

        foreach ($this->rutasDetalleHttp() as $ruta) {
            $this->consultarHttp($ruta, $token)
                ->assertUnauthorized()
                ->assertJsonPath('success', false);
        }
    }

    private function crearTokenHttp(int $usuarioId): string
    {
        return Usuario::findOrFail($usuarioId)
            ->createToken('prueba-hu13')
            ->plainTextToken;
    }

    private function rutasDetalleHttp(): array
    {
        $postulacion = $this->crearPostulacion($this->candidato);

        return [
            '/api/v1/vacantes/'.$this->vacante->getKey(),
            '/api/v1/candidatos/me/postulaciones/'
                .$postulacion->getKey().'/vacante',
        ];
    }

    private function consultarHttp(
        string $ruta,
        ?string $token = null
    ): TestResponse {
        $this->app->make('auth')->forgetGuards();

        $this->withoutHeader('Authorization');

        $cabeceras = $token === null
            ? []
            : ['Authorization' => 'Bearer '.$token];

        return $this->getJson($ruta, $cabeceras);
    }

    private function consultar(): array
    {
        return app(DetalleVacanteService::class)->disponible(
            (int) $this->vacante->getKey(),
            $this->candidato
        );
    }

    private function resolverRecurso(array $detalle): array
    {
        return (new VacanteDetalleResource($detalle))->resolve(
            Request::create('/api/v1/vacantes/1', 'GET')
        );
    }

    private function prepararDatos(): void
    {
        $this->candidato = $this->crearCandidato();

        $this->empresaUsuarioId = $this->crearUsuario('empresa');

        $empresaId = DB::table('empresas')->insertGetId([
            'usuario_id' => $this->empresaUsuarioId,
            'encargado_empresa' => 'Responsable de prueba',
            'razon_social' => 'Empresa HU13 '.bin2hex(random_bytes(6)),
            'sector' => 'Tecnología e Informática',
            'descripcion' => 'Empresa dedicada a desarrollar soluciones digitales.',
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
                'Falta el nivel global activo Junior en la base de pruebas.'
            );
        }

        $areaId = DB::table('areas_estudio')->insertGetId([
            'nombre' => 'Área HU13 '.bin2hex(random_bytes(6)),
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->vacante = Empleo::create([
            'empresa_id' => $empresaId,
            'titulo' => 'Analista de soluciones HU13',
            'descripcion' => "**Responsabilidades**\n\n- Desarrollar soluciones.\n- Documentar resultados.",
            'nivel_puesto_id' => $nivelId,
            'area_estudio_id' => $areaId,
            'tipo_empleo' => 'remoto',
            'ubicacion' => null,
            'salario_min' => null,
            'salario_max' => null,
            'fecha_inicio_vigencia' => '2026-10-25',
            'fecha_fin_vigencia' => '2026-10-26',
            'estado_empleo' => Empleo::ACTIVA,
            'numero_vacantes' => 2,
        ]);

        $tecnicaId = $this->crearHabilidad(
            'tecnica',
            $areaId
        );

        $blandaId = $this->crearHabilidad(
            'blanda',
            null
        );

        $this->vacante->habilidades()->attach([
            $tecnicaId => ['tipo_requisito' => 'must_have'],
            $blandaId => ['tipo_requisito' => 'nice_to_have'],
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
            'correo' => 'hu13-'.bin2hex(random_bytes(10)).'@example.test',
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
            'apellidos' => 'Detalle HU13',
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

    private function crearHabilidad(
        string $tipo,
        ?int $areaId
    ): int {
        return (int) DB::table('habilidades')->insertGetId([
            'nombre' => 'Habilidad HU13 '.bin2hex(random_bytes(6)),
            'tipo_habilidad' => $tipo,
            'area_estudio_id' => $areaId,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearPostulacion(
        Candidato $candidato,
        bool $retirada = false
    ): Postulacion {
        $estadoId = DB::table('estados_postulacion')
            ->where('nombre', 'Recibido')
            ->value('id');

        if ($estadoId === null) {
            $estadoId = DB::table('estados_postulacion')->insertGetId([
                'nombre' => 'Recibido',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return Postulacion::create([
            'candidato_id' => $candidato->getKey(),
            'empleo_id' => $this->vacante->getKey(),
            'estado_postulacion_id' => $estadoId,
            'fecha_postulacion' => now(),
            'retirada_en' => $retirada ? now() : null,
        ]);
    }
}
