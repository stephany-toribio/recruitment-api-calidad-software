<?php

namespace Tests\Feature\Postulaciones;

use App\Models\Empleo;
use App\Models\Empresa;
use App\Models\EstadoPostulacion;
use App\Models\Postulacion;
use App\Models\Usuario;
use App\Services\Postulaciones\NotificacionPostulacionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class KanbanTest extends TestCase
{
    private Usuario $usuario;
    private Empresa $empresa;
    private Empleo $vacante;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertTrue($this->app->environment('testing'));
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        $this->crearTablas();

        DB::table('roles')->insert([
            ['id' => 1, 'nombre' => 'empresa'],
            ['id' => 2, 'nombre' => 'candidato'],
            ['id' => 3, 'nombre' => 'admin'],
        ]);

        foreach (EstadoPostulacion::ORDEN as $nombre) {
            EstadoPostulacion::create(['nombre' => $nombre]);
        }

        foreach ([
            'Perfil no se ajusta',
            'Expectativa salarial alta',
            'Proceso cerrado',
        ] as $motivo) {
            DB::table('motivos_rechazo')->insert([
                'motivo' => $motivo,
            ]);
        }

        $this->usuario = Usuario::create([
            'correo' => 'empresa.kanban@prueba.com',
            'password' => 'PruebaKanban2026#',
            'rol_id' => 1,
            'estado_cuenta' => 'activo',
        ]);

        $this->empresa = Empresa::create([
            'usuario_id' => $this->usuario->id,
        ]);

        $this->vacante = Empleo::create([
            'empresa_id' => $this->empresa->id,
            'titulo' => 'Vacante QA Kanban',
            'estado_empleo' => Empleo::ACTIVA,
            'numero_vacantes' => 1,
            'fecha_inicio_vigencia' => now()->subDay()->toDateString(),
            'fecha_fin_vigencia' => now()->addMonth()->toDateString(),
        ]);

        // Por defecto, ninguna prueba envía correos reales.
        $this->instance(
            NotificacionPostulacionService::class,
            Mockery::mock(NotificacionPostulacionService::class)
                ->shouldReceive('notificarDecision')
                ->zeroOrMoreTimes()
                ->andReturnNull()
                ->getMock()
        );

        Sanctum::actingAs($this->usuario);
    }

    private function crearTablas(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });

        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('correo');
            $table->string('password');
            $table->unsignedBigInteger('rol_id');
            $table->string('estado_cuenta');
            $table->unsignedInteger('intentos_fallidos')->default(0);
            $table->timestamp('bloqueado_hasta')->nullable();
            $table->timestamps();
        });

        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('usuario_id');
            $table->timestamps();
        });

        Schema::create('candidatos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellidos');
            $table->string('celular')->nullable();
            $table->timestamps();
        });

        Schema::create('empleos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('titulo');
            $table->string('estado_empleo');
            $table->unsignedInteger('numero_vacantes');
            $table->date('fecha_inicio_vigencia');
            $table->date('fecha_fin_vigencia');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('estados_postulacion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
        });

        Schema::create('motivos_rechazo', function (Blueprint $table) {
            $table->id();
            $table->string('motivo');
            $table->timestamps();
        });

        Schema::create('postulaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('candidato_id');
            $table->unsignedBigInteger('empleo_id');
            $table->unsignedBigInteger('estado_postulacion_id');
            $table->timestamp('fecha_postulacion');
            $table->timestamp('retirada_en')->nullable();
            $table->timestamps();
            $table->unique(['candidato_id', 'empleo_id']);
        });

        Schema::create('historial_postulaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('postulacion_id');
            $table->unsignedBigInteger('estado_postulacion_id');
            $table->unsignedBigInteger('motivo_rechazo_id')->nullable();
            $table->timestamp('fecha_cambio');
            $table->timestamps();
        });
    }

    private function postular(
        string $estado = EstadoPostulacion::RECIBIDO,
        bool $retirada = false
    ): Postulacion {
        $candidatoId = DB::table('candidatos')->insertGetId([
            'nombre' => 'Ana',
            'apellidos' => 'Torres',
            'celular' => '999999999',
        ]);

        return Postulacion::create([
            'candidato_id' => $candidatoId,
            'empleo_id' => $this->vacante->id,
            'estado_postulacion_id' => EstadoPostulacion::idDe($estado),
            'fecha_postulacion' => now(),
            'retirada_en' => $retirada ? now() : null,
        ]);
    }

    private function url(
        ?Postulacion $postulacion = null,
        ?string $accion = null
    ): string {
        $base = "/api/v1/empresas/me/vacantes/{$this->vacante->id}/postulaciones";

        return $postulacion === null
            ? $base
            : "{$base}/{$postulacion->id}/{$accion}";
    }

    public function test_tablero_tiene_cinco_columnas_y_oculta_retiradas_y_contacto(): void
    {
        $visible = $this->postular();
        $this->postular(EstadoPostulacion::EN_REVISION, true);

        $respuesta = $this->getJson($this->url())
            ->assertOk()
            ->assertJsonCount(5, 'data.columnas')
            ->assertJsonPath('data.vacante.solo_lectura', false)
            ->assertJsonPath('data.vacante.cupos_disponibles', 1);

        $columnas = $respuesta->json('data.columnas');

        $this->assertSame([
            'recibido',
            'en_revision',
            'entrevista',
            'contratado',
            'rechazado',
        ], array_column(array_column($columnas, 'estado'), 'codigo'));

        $tarjetas = collect($columnas)->pluck('postulaciones')->flatten(1);

        $this->assertSame([$visible->id], $tarjetas->pluck('id')->all());
        $this->assertSame(
            ['nombre', 'apellidos'],
            array_keys($tarjetas->first()['candidato'])
        );
        $this->assertArrayNotHasKey('porcentaje_match', $tarjetas->first());
    }

    public function test_avanza_dos_etapas_y_registra_historial(): void
    {
        $postulacion = $this->postular();

        $this->patchJson($this->url($postulacion, 'avanzar'))
            ->assertOk()
            ->assertJsonPath('data.estado.codigo', 'en_revision');

        $this->patchJson($this->url($postulacion, 'avanzar'))
            ->assertOk()
            ->assertJsonPath('data.estado.codigo', 'entrevista');

        $this->patchJson($this->url($postulacion, 'avanzar'))
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'INVALID_TRANSITION');

        $this->assertDatabaseCount('historial_postulaciones', 2);
        $this->assertNull($postulacion->fresh()->retirada_en);
    }

    public function test_no_permite_contratar_desde_recibido(): void
    {
        $postulacion = $this->postular();

        $this->patchJson($this->url($postulacion, 'contratar'))
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'INVALID_TRANSITION');

        $this->assertDatabaseCount('historial_postulaciones', 0);
        $this->assertSame(
            EstadoPostulacion::idDe(EstadoPostulacion::RECIBIDO),
            $postulacion->fresh()->estado_postulacion_id
        );
    }

    public function test_contratacion_respeta_cupo_y_actualiza_tablero(): void
    {
        $primera = $this->postular(EstadoPostulacion::ENTREVISTA);
        $segunda = $this->postular(EstadoPostulacion::ENTREVISTA);

        $this->patchJson($this->url($primera, 'contratar'))
            ->assertOk()
            ->assertJsonPath('data.estado.codigo', 'contratado');

        $this->patchJson($this->url($segunda, 'contratar'))
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'HIRING_LIMIT_REACHED');

        $this->getJson($this->url())
            ->assertOk()
            ->assertJsonPath('data.vacante.contratados', 1)
            ->assertJsonPath('data.vacante.cupos_disponibles', 0);

        $this->assertDatabaseCount('historial_postulaciones', 1);
    }

    public function test_rechazo_valida_motivo_y_lo_guarda_en_historial(): void
    {
        $postulacion = $this->postular(EstadoPostulacion::EN_REVISION);

        DB::table('motivos_rechazo')->insert([
            'id' => 99,
            'motivo' => 'Motivo fuera del contrato',
        ]);

        foreach ([[], ['motivo_rechazo_id' => 999], ['motivo_rechazo_id' => 99]] as $body) {
            $this->patchJson($this->url($postulacion, 'rechazar'), $body)
                ->assertStatus(422)
                ->assertJsonValidationErrors('motivo_rechazo_id');
        }

        $this->patchJson($this->url($postulacion, 'rechazar'), [
            'motivo_rechazo_id' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.estado.codigo', 'rechazado')
            ->assertJsonPath('data.motivo_rechazo', 'Perfil no se ajusta');

        $this->assertDatabaseHas('historial_postulaciones', [
            'postulacion_id' => $postulacion->id,
            'estado_postulacion_id' => EstadoPostulacion::idDe(
                EstadoPostulacion::RECHAZADO
            ),
            'motivo_rechazo_id' => 1,
        ]);

        $this->getJson('/api/v1/motivos-rechazo')
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_estados_terminales_no_permiten_movimientos(): void
    {
        foreach (EstadoPostulacion::TERMINALES as $estado) {
            $postulacion = $this->postular($estado);

            foreach (['avanzar', 'contratar', 'rechazar'] as $accion) {
                $this->patchJson($this->url($postulacion, $accion), [
                    'motivo_rechazo_id' => 1,
                ])
                    ->assertStatus(409)
                    ->assertJsonPath('error_code', 'INVALID_TRANSITION');
            }
        }

        $this->assertDatabaseCount('historial_postulaciones', 0);
    }

    public function test_retirada_solo_aparece_en_historial_y_no_se_mueve(): void
    {
        $postulacion = $this->postular(EstadoPostulacion::EN_REVISION, true);

        foreach (['avanzar', 'rechazar', 'contratar'] as $accion) {
            $this->patchJson($this->url($postulacion, $accion), [
                'motivo_rechazo_id' => 1,
            ])
                ->assertStatus(409)
                ->assertJsonPath('error_code', 'APPLICATION_WITHDRAWN');
        }

        $this->getJson($this->url().'/historial')
            ->assertOk()
            ->assertJsonPath('data.postulaciones.0.id', $postulacion->id)
            ->assertJsonPath('data.postulaciones.0.retirada', true)
            ->assertJsonPath('data.postulaciones.0.estado.codigo', 'en_revision');

        $this->assertDatabaseCount('historial_postulaciones', 0);
    }

    public function test_pausada_y_vencida_permiten_gestion(): void
    {
        $postulacion = $this->postular();

        $this->vacante->update(['estado_empleo' => Empleo::PAUSADA]);

        $this->patchJson($this->url($postulacion, 'avanzar'))
            ->assertOk();

        $this->vacante->update([
            'estado_empleo' => Empleo::ACTIVA,
            'fecha_inicio_vigencia' => now()->subMonth()->toDateString(),
            'fecha_fin_vigencia' => now()->subDay()->toDateString(),
        ]);

        $this->patchJson($this->url($postulacion, 'avanzar'))
            ->assertOk()
            ->assertJsonPath('data.estado.codigo', 'entrevista');
    }

    public function test_finalizada_es_solo_lectura_y_eliminada_solo_historial(): void
    {
        $postulacion = $this->postular();

        $this->vacante->update(['estado_empleo' => Empleo::FINALIZADA]);

        $this->getJson($this->url())
            ->assertOk()
            ->assertJsonPath('data.vacante.solo_lectura', true);

        foreach (['avanzar', 'rechazar', 'contratar'] as $accion) {
            $this->patchJson($this->url($postulacion, $accion), [
                'motivo_rechazo_id' => 1,
            ])
                ->assertStatus(409)
                ->assertJsonPath('error_code', 'VACANCY_FINALIZED');
        }

        $this->getJson($this->url().'/historial')->assertOk();

        $this->vacante->delete();

        $this->getJson($this->url())
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'VACANCY_DELETED');

        foreach (['avanzar', 'rechazar', 'contratar'] as $accion) {
            $this->patchJson($this->url($postulacion, $accion), [
                'motivo_rechazo_id' => 1,
            ])
                ->assertStatus(409)
                ->assertJsonPath('error_code', 'VACANCY_DELETED');
        }

        $this->getJson($this->url().'/historial')
            ->assertOk()
            ->assertJsonPath('data.postulaciones.0.id', $postulacion->id);
    }

    public function test_vacante_ajena_devuelve_403_y_postulacion_ajena_404(): void
    {
        $postulacion = $this->postular();
        $empresaAjena = Empresa::create(['usuario_id' => 999]);

        $this->vacante->update(['empresa_id' => $empresaAjena->id]);

        $this->getJson($this->url())->assertForbidden();
        $this->getJson($this->url().'/historial')->assertForbidden();

        $this->patchJson($this->url($postulacion, 'avanzar'))
            ->assertForbidden();

        $this->vacante->update(['empresa_id' => $this->empresa->id]);
        $postulacion->update(['empleo_id' => 999]);

        $this->patchJson($this->url($postulacion, 'avanzar'))
            ->assertNotFound();

        $this->patchJson(
            $this->url().'/999999/avanzar'
        )->assertNotFound();
    }

    public function test_roles_distintos_de_empresa_no_tienen_acceso(): void
    {
        foreach ([2, 3] as $rolId) {
            $usuario = Usuario::create([
                'correo' => "rol{$rolId}@prueba.com",
                'password' => 'PruebaKanban2026#',
                'rol_id' => $rolId,
                'estado_cuenta' => 'activo',
            ]);

            Sanctum::actingAs($usuario);

            $this->getJson($this->url())->assertForbidden();
            $this->getJson('/api/v1/motivos-rechazo')->assertForbidden();
        }
    }

    public function test_sin_autenticacion_devuelve_401(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson($this->url())->assertUnauthorized();
        $this->getJson('/api/v1/motivos-rechazo')->assertUnauthorized();
    }

    public function test_notificacion_ocurre_despues_del_commit(): void
    {
        $postulacion = $this->postular(EstadoPostulacion::ENTREVISTA);

        $mock = Mockery::mock(NotificacionPostulacionService::class);

        $mock->shouldReceive('notificarDecision')
            ->once()
            ->with(Mockery::on(function (Postulacion $actual) use ($postulacion) {
                $this->assertSame(0, DB::transactionLevel());
                $this->assertSame($postulacion->id, $actual->id);

                $this->assertDatabaseHas('postulaciones', [
                    'id' => $actual->id,
                    'estado_postulacion_id' => EstadoPostulacion::idDe(
                        EstadoPostulacion::CONTRATADO
                    ),
                ]);

                $this->assertDatabaseHas('historial_postulaciones', [
                    'postulacion_id' => $actual->id,
                ]);

                return true;
            }));

        $this->instance(NotificacionPostulacionService::class, $mock);

        $this->patchJson($this->url($postulacion, 'contratar'))
            ->assertOk();
    }

    public function test_fallo_del_correo_no_revierte_el_rechazo(): void
    {
        $postulacion = $this->postular(EstadoPostulacion::EN_REVISION);

        $mock = Mockery::mock(NotificacionPostulacionService::class);

        $mock->shouldReceive('notificarDecision')
            ->once()
            ->andReturnUsing(function () {
                $this->assertSame(0, DB::transactionLevel());

                throw new RuntimeException('Fallo simulado del correo');
            });

        $this->instance(NotificacionPostulacionService::class, $mock);

        $this->patchJson($this->url($postulacion, 'rechazar'), [
            'motivo_rechazo_id' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.estado.codigo', 'rechazado');

        $this->assertDatabaseHas('postulaciones', [
            'id' => $postulacion->id,
            'estado_postulacion_id' => EstadoPostulacion::idDe(
                EstadoPostulacion::RECHAZADO
            ),
        ]);

        $this->assertDatabaseCount('historial_postulaciones', 1);
    }

        public function test_no_permite_rechazar_desde_recibido(): void
    {
        $postulacion = $this->postular(EstadoPostulacion::RECIBIDO);

        $mock = Mockery::mock(NotificacionPostulacionService::class);
        $mock->shouldNotReceive('notificarDecision');

        $this->instance(NotificacionPostulacionService::class, $mock);

        $this->patchJson($this->url($postulacion, 'rechazar'), [
            'motivo_rechazo_id' => 1,
        ])
            ->assertStatus(409)
            ->assertJsonPath('error_code', 'INVALID_TRANSITION');

        $this->assertSame(
            EstadoPostulacion::idDe(EstadoPostulacion::RECIBIDO),
            $postulacion->fresh()->estado_postulacion_id
        );

        $this->assertNull($postulacion->fresh()->retirada_en);
        $this->assertDatabaseCount('postulaciones', 1);
        $this->assertDatabaseCount('historial_postulaciones', 0);
    }

    public function test_permite_rechazar_desde_entrevista(): void
    {
        $postulacion = $this->postular(EstadoPostulacion::ENTREVISTA);

        $this->patchJson($this->url($postulacion, 'rechazar'), [
            'motivo_rechazo_id' => 1,
        ])
            ->assertOk()
            ->assertJsonPath('data.estado.codigo', 'rechazado')
            ->assertJsonPath('data.motivo_rechazo', 'Perfil no se ajusta');

        $rechazadoId = EstadoPostulacion::idDe(
            EstadoPostulacion::RECHAZADO
        );

        $this->assertSame(
            $rechazadoId,
            $postulacion->fresh()->estado_postulacion_id
        );

        $this->assertDatabaseHas('historial_postulaciones', [
            'postulacion_id' => $postulacion->id,
            'estado_postulacion_id' => $rechazadoId,
            'motivo_rechazo_id' => 1,
        ]);

        $this->assertDatabaseCount('historial_postulaciones', 1);
        $this->assertNull($postulacion->fresh()->retirada_en);
    }
}