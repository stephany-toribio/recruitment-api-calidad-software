<?php

namespace Tests\Feature\Vacantes;

use App\Models\Empleo;
use App\Models\RevisionVacante;
use App\Models\Usuario;
use App\Services\Vacantes\RevisionVacanteService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ListadoModeracionTest extends TestCase
{
    private const RUTA = '/api/v1/admin/vacantes';

    private Usuario $admin;

    private int $empresaId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertTrue($this->app->environment('testing'));
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        $this->crearTablas();

        DB::table('roles')->insert([
            ['id' => 1, 'nombre' => 'admin'],
            ['id' => 2, 'nombre' => 'empresa'],
            ['id' => 3, 'nombre' => 'candidato'],
        ]);

        $this->admin = $this->crearUsuario(1);

        $empresaUsuario = $this->crearUsuario(2);

        $this->empresaId = DB::table('empresas')->insertGetId([
            'usuario_id' => $empresaUsuario->id,
            'razon_social' => 'Empresa QA Moderacion',
            'ruc' => '20999990001',
            'sector' => 'Tecnología e Informática',
        ]);

        Sanctum::actingAs($this->admin);
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
            $table->string('razon_social');
            $table->string('ruc');
            $table->string('sector');
            $table->timestamps();
        });

        foreach (['niveles_puesto', 'areas_estudio'] as $nombre) {
            Schema::create($nombre, function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->timestamps();
            });
        }

        Schema::create('habilidades', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('tipo_habilidad');
            $table->timestamps();
        });

        Schema::create('empleos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empresa_id');
            $table->string('titulo');
            $table->text('descripcion');
            $table->json('requisitos')->nullable();
            $table->json('beneficios')->nullable();
            $table->unsignedBigInteger('nivel_puesto_id')->nullable();
            $table->unsignedBigInteger('area_estudio_id')->nullable();
            $table->string('tipo_empleo');
            $table->string('ubicacion')->nullable();
            $table->decimal('salario_min', 10, 2)->nullable();
            $table->decimal('salario_max', 10, 2)->nullable();
            $table->date('fecha_inicio_vigencia');
            $table->date('fecha_fin_vigencia');
            $table->string('estado_empleo');
            $table->unsignedInteger('numero_vacantes');
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('empleo_habilidades', function (Blueprint $table) {
            $table->unsignedBigInteger('empleo_id');
            $table->unsignedBigInteger('habilidad_id');
            $table->string('tipo_requisito');
            $table->timestamps();
        });

        Schema::create('revisiones_vacante', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('empleo_id');
            $table->unsignedBigInteger('admin_id');
            $table->string('decision');
            $table->text('motivo')->nullable();
            $table->timestamp('invalidada_en')->nullable();
            $table->timestamps();
        });
    }

    private function crearUsuario(int $rolId): Usuario
    {
        return Usuario::create([
            'correo' => 'qa.'.bin2hex(random_bytes(6)).'@example.test',
            'password' => 'PruebaModeracion2026#',
            'rol_id' => $rolId,
            'estado_cuenta' => 'activo',
        ]);
    }

    private function crearVacante(?string $decision = null): Empleo
    {
        $vacante = Empleo::create([
            'empresa_id' => $this->empresaId,
            'titulo' => 'Vacante QA Moderacion',
            'descripcion' => str_repeat('Descripción de prueba. ', 4),
            'tipo_empleo' => 'remoto',
            'estado_empleo' => Empleo::ACTIVA,
            'numero_vacantes' => 1,
            'fecha_inicio_vigencia' => now()->toDateString(),
            'fecha_fin_vigencia' => now()->addDays(30)->toDateString(),
        ]);

        if ($decision !== null) {
            RevisionVacante::create([
                'empleo_id' => $vacante->id,
                'admin_id' => $this->admin->id,
                'decision' => $decision,
                'motivo' => $decision === RevisionVacante::NO_APROBADA
                    ? 'La descripción requiere ajustes.'
                    : null,
            ]);
        }

        return $vacante;
    }

    public function test_filtra_por_revision_vigente(): void
    {
        $pendiente = $this->crearVacante();
        $aprobada = $this->crearVacante(RevisionVacante::APROBADA);
        $noAprobada = $this->crearVacante(RevisionVacante::NO_APROBADA);

        foreach ([
            'pendiente' => $pendiente,
            'aprobada' => $aprobada,
            'no_aprobada' => $noAprobada,
        ] as $decision => $vacante) {
            $this->getJson(self::RUTA.'?revision='.$decision)
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $vacante->id)
                ->assertJsonPath('data.0.revision.estado', $decision)
                ->assertJsonPath('meta.total', 1);
        }
    }

    public function test_todas_lista_cada_estado_sin_eliminadas(): void
    {
        $pendiente = $this->crearVacante();
        $aprobada = $this->crearVacante(RevisionVacante::APROBADA);
        $noAprobada = $this->crearVacante(RevisionVacante::NO_APROBADA);
        $this->crearVacante(RevisionVacante::APROBADA)->delete();

        $respuesta = $this->getJson(self::RUTA.'?revision=todas')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.total', 3);

        $this->assertSame(
            [
                $noAprobada->id => 'no_aprobada',
                $aprobada->id => 'aprobada',
                $pendiente->id => 'pendiente',
            ],
            array_column(
                array_map(fn (array $v) => ['id' => $v['id'], 'estado' => $v['revision']['estado']], $respuesta->json('data')),
                'estado',
                'id'
            )
        );
    }

    public function test_ordena_la_cola_por_antiguedad_y_las_decididas_por_decision(): void
    {
        $antigua = $this->crearVacante();
        $this->travel(1)->days();
        $reciente = $this->crearVacante();
        $this->travel(1)->days();
        $aprobadaAyer = $this->crearVacante(RevisionVacante::APROBADA);

        $this->getJson(self::RUTA.'?revision=pendiente')
            ->assertJsonPath('data.0.id', $antigua->id)
            ->assertJsonPath('data.1.id', $reciente->id);

        $this->travel(1)->days();
        RevisionVacante::create([
            'empleo_id' => $antigua->id,
            'admin_id' => $this->admin->id,
            'decision' => RevisionVacante::APROBADA,
        ]);

        $this->getJson(self::RUTA.'?revision=aprobada')
            ->assertJsonPath('data.0.id', $antigua->id)
            ->assertJsonPath('data.1.id', $aprobadaAyer->id);

        $this->getJson(self::RUTA.'?revision=todas')
            ->assertJsonPath('data.0.id', $aprobadaAyer->id)
            ->assertJsonPath('data.1.id', $reciente->id)
            ->assertJsonPath('data.2.id', $antigua->id);
    }

    public function test_todas_pagina_igual_que_los_demas_filtros(): void
    {
        foreach (range(1, 6) as $numero) {
            $this->crearVacante();
            $this->crearVacante(RevisionVacante::APROBADA);
        }

        $this->getJson(self::RUTA.'?revision=todas&page=2')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.total', 12)
            ->assertJsonPath('meta.last_page', 2);
    }

    public function test_sin_filtro_lista_pendientes_y_conserva_ruta_anterior(): void
    {
        $pendiente = $this->crearVacante();
        $this->crearVacante(RevisionVacante::APROBADA);

        $this->getJson(self::RUTA)
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pendiente->id);

        $this->getJson(self::RUTA.'/pendientes')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $pendiente->id);
    }

    public function test_decisiones_se_recuperan_con_otro_administrador(): void
    {
        $aprobada = $this->crearVacante(RevisionVacante::APROBADA);
        $noAprobada = $this->crearVacante(RevisionVacante::NO_APROBADA);

        foreach ([
            'aprobada' => $aprobada,
            'no_aprobada' => $noAprobada,
        ] as $decision => $vacante) {
            $this->getJson(self::RUTA.'?revision='.$decision)
                ->assertOk()
                ->assertJsonPath('data.0.id', $vacante->id);
        }

        Sanctum::actingAs($this->crearUsuario(1));

        foreach ([
            'aprobada' => $aprobada,
            'no_aprobada' => $noAprobada,
        ] as $decision => $vacante) {
            $this->getJson(self::RUTA.'?revision='.$decision)
                ->assertOk()
                ->assertJsonCount(1, 'data')
                ->assertJsonPath('data.0.id', $vacante->id);
        }
    }

    public function test_aprobacion_invalidada_vuelve_a_pendientes(): void
    {
        $vacante = $this->crearVacante(RevisionVacante::APROBADA);

        app(RevisionVacanteService::class)->reabrirRevision($vacante);

        $this->getJson(self::RUTA.'?revision=aprobada')
            ->assertOk()
            ->assertJsonCount(0, 'data')
            ->assertJsonPath('meta.total', 0);

        $this->getJson(self::RUTA.'?revision=pendiente')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $vacante->id)
            ->assertJsonPath('data.0.revision.estado', 'pendiente');

        $this->assertDatabaseCount('revisiones_vacante', 1);

        $this->assertNotNull(
            RevisionVacante::where('empleo_id', $vacante->id)
                ->firstOrFail()
                ->invalidada_en
        );
    }

    public function test_pagina_sin_duplicados_ni_faltantes(): void
    {
        $esperados = [];

        foreach (range(1, 13) as $numero) {
            $esperados[] = $this->crearVacante(
                RevisionVacante::APROBADA
            )->id;
        }

        $primera = $this->getJson(self::RUTA.'?revision=aprobada&page=1')
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 13)
            ->assertJsonPath('meta.last_page', 2);

        $segunda = $this->getJson(self::RUTA.'?revision=aprobada&page=2')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.total', 13)
            ->assertJsonPath('meta.last_page', 2);

        $idsPrimera = array_column($primera->json('data'), 'id');
        $idsSegunda = array_column($segunda->json('data'), 'id');
        $obtenidos = array_merge($idsPrimera, $idsSegunda);

        $this->assertSame([], array_intersect($idsPrimera, $idsSegunda));
        $this->assertSame(array_reverse($esperados), $obtenidos);
    }

    public function test_excluye_eliminadas_de_cada_categoria(): void
    {
        foreach ([
            null,
            RevisionVacante::APROBADA,
            RevisionVacante::NO_APROBADA,
        ] as $decision) {
            $this->crearVacante($decision)->delete();
        }

        foreach (['pendiente', 'aprobada', 'no_aprobada'] as $decision) {
            $this->getJson(self::RUTA.'?revision='.$decision)
                ->assertOk()
                ->assertJsonCount(0, 'data')
                ->assertJsonPath('meta.total', 0);
        }
    }

    public function test_rechaza_parametros_invalidos(): void
    {
        foreach ([
            ['revision=otra', 'revision'],
            ['revision=', 'revision'],
            ['page=0', 'page'],
            ['page=-1', 'page'],
            ['page=abc', 'page'],
        ] as [$consulta, $campo]) {
            $this->getJson(self::RUTA.'?'.$consulta)
                ->assertStatus(422)
                ->assertJsonValidationErrors($campo);
        }
    }

    public function test_empresa_y_candidato_no_tienen_acceso(): void
    {
        foreach ([2, 3] as $rolId) {
            Sanctum::actingAs($this->crearUsuario($rolId));

            $this->getJson(self::RUTA.'?revision=aprobada')
                ->assertForbidden();
        }
    }

    public function test_sin_sesion_devuelve_401(): void
    {
        $this->app['auth']->forgetGuards();

        $this->getJson(self::RUTA.'?revision=aprobada')
            ->assertUnauthorized();
    }
}