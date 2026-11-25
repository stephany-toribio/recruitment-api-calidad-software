<?php

namespace Tests\MySql;

use App\Models\Candidato;
use App\Models\Empleo;
use App\Models\Postulacion;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class Qa05PropiedadMySqlTest extends TestCase
{
    private bool $transaccionIniciada = false;
    private Usuario $empresaUno;
    private Usuario $empresaDos;
    private Usuario $candidatoUno;
    private Usuario $candidatoDos;
    private Usuario $admin;
    private int $empresaId;
    private Candidato $perfilUno;
    private Candidato $perfilDos;
    private Empleo $vacante;
    private int $estadoId;

    protected function setUp(): void
    {
        parent::setUp();

        // No escribir datos antes de verificar aislamiento y base efectiva.
        $this->assertFalse($this->app->configurationIsCached());
        $this->assertTrue($this->app->environment('testing'));
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame('mysql', DB::connection()->getConfig('host'));
        $this->assertSame(
            'portalempleoshu04testing',
            DB::selectOne('SELECT DATABASE() AS nombre')->nombre
        );

        DB::beginTransaction();
        $this->transaccionIniciada = true;

        try {
            $this->prepararDatos();
        } catch (\Throwable $error) {
            DB::rollBack();
            $this->transaccionIniciada = false;
            throw $error;
        }
    }

    protected function tearDown(): void
    {
        try {
            if ($this->transaccionIniciada) {
                DB::rollBack();
            }
        } finally {
            parent::tearDown();
        }
    }

    private function usuario(string $rol): Usuario
    {
        $rolId = DB::table('roles')->where('nombre', $rol)->value('id');
        if ($rolId === null) {
            $rolId = DB::table('roles')->insertGetId([
                'nombre' => $rol,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return Usuario::create([
            'rol_id' => $rolId,
            'correo' => 'qa05-'.bin2hex(random_bytes(10)).'@example.test',
            'password' => 'Qa05SoloIntegracion#2026',
            'estado_cuenta' => 'activo',
        ]);
    }

    private function empresa(Usuario $usuario): int
    {
        return (int) DB::table('empresas')->insertGetId([
            'usuario_id' => $usuario->id,
            'encargado_empresa' => 'Responsable ficticio QA05',
            'razon_social' => 'Empresa QA05 '.bin2hex(random_bytes(6)),
            'sector' => 'Tecnología e Informática',
            'descripcion' => 'Datos de integracion descartables.',
            'ruc' => '20'.random_int(100000000, 999999999),
            'sitio_web' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function candidato(Usuario $usuario): Candidato
    {
        return Candidato::create([
            'usuario_id' => $usuario->id,
            'nombre' => 'Persona QA05 '.$usuario->id,
            'apellidos' => 'Prueba de propiedad',
            'dni' => null,
            'fecha_nacimiento' => '2000-01-01',
            'genero' => null,
            'busca_primer_empleo' => true,
            'celular' => '9'.str_pad((string) $usuario->id, 8, '0', STR_PAD_LEFT),
        ]);
    }

    private function prepararDatos(): void
    {
        $this->admin = $this->usuario('admin');
        $this->empresaUno = $this->usuario('empresa');
        $this->empresaDos = $this->usuario('empresa');
        $this->empresaId = $this->empresa($this->empresaUno);
        $this->empresa($this->empresaDos);
        $this->candidatoUno = $this->usuario('candidato');
        $this->candidatoDos = $this->usuario('candidato');
        $this->perfilUno = $this->candidato($this->candidatoUno);
        $this->perfilDos = $this->candidato($this->candidatoDos);

        $nivelId = DB::table('niveles_puesto')
            ->whereNull('candidato_id')->where('estado', 'activo')->value('id');
        if ($nivelId === null) {
            $nivelId = DB::table('niveles_puesto')->insertGetId([
                'nombre' => 'Nivel QA05 '.bin2hex(random_bytes(6)),
                'candidato_id' => null,
                'estado' => 'activo',
                'orden' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $areaId = DB::table('areas_estudio')->insertGetId([
            'nombre' => 'Area QA05 '.bin2hex(random_bytes(6)),
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->vacante = Empleo::create([
            'empresa_id' => $this->empresaId,
            'titulo' => 'Vacante de propiedad QA05',
            'descripcion' => str_repeat('Vacante ficticia para verificar permisos. ', 3),
            'nivel_puesto_id' => $nivelId,
            'area_estudio_id' => $areaId,
            'tipo_empleo' => 'remoto',
            'ubicacion' => null,
            'salario_min' => null,
            'salario_max' => null,
            'fecha_inicio_vigencia' => now('America/Lima')->subDay()->toDateString(),
            'fecha_fin_vigencia' => now('America/Lima')->addDays(7)->toDateString(),
            'estado_empleo' => Empleo::ACTIVA,
            'numero_vacantes' => 2,
        ]);

        DB::table('revisiones_vacante')->insert([
            'empleo_id' => $this->vacante->id,
            'admin_id' => $this->admin->id,
            'decision' => 'aprobada',
            'motivo' => null,
            'invalidada_en' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pagos_vacante')->insert([
            'empleo_id' => $this->vacante->id,
            'resultado' => 'exitoso',
            'monto' => 200,
            'moneda' => 'PEN',
            'tarjeta_ultimos4' => '1111',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $estado = DB::table('estados_postulacion')->where('nombre', \App\Models\EstadoPostulacion::RECIBIDO)->value('id');
        $this->estadoId = (int) ($estado ?? DB::table('estados_postulacion')->insertGetId([
            'nombre' => \App\Models\EstadoPostulacion::RECIBIDO,
            'created_at' => now(),
            'updated_at' => now(),
        ]));
    }

    private function token(Usuario $usuario): string
    {
        return $usuario->createToken('qa05-integracion')->plainTextToken;
    }

    private function api(string $metodo, string $ruta, array $datos = [], ?string $token = null)
    {
        $this->app->make('auth')->forgetGuards();
        $this->withoutHeader('Authorization');

        return $this->json($metodo, $ruta, $datos, $token === null
            ? [] : ['Authorization' => 'Bearer '.$token]);
    }

    private function fila(string $tabla, int $id): array
    {
        return (array) DB::table($tabla)->where('id', $id)->first();
    }

    private function postulacion(): Postulacion
    {
        return Postulacion::create([
            'candidato_id' => $this->perfilUno->id,
            'empleo_id' => $this->vacante->id,
            'estado_postulacion_id' => $this->estadoId,
            'fecha_postulacion' => now(),
            'retirada_en' => null,
        ]);
    }

    public function test_cp06_01_perfil_propio_procede_del_token(): void
    {
        $this->api('GET', '/api/v1/candidatos/me', [], $this->token($this->candidatoUno))
            ->assertOk()->assertJsonPath('data.id', $this->perfilUno->id)
            ->assertJsonPath('data.usuario_id', $this->candidatoUno->id)
            ->assertJsonPath('data.correo', $this->candidatoUno->correo);
    }

    public function test_cp06_02_edita_solo_el_perfil_propio(): void
    {
        $ajeno = $this->fila('candidatos', $this->perfilDos->id);

        $this->api('PUT', '/api/v1/candidatos/me', [
            'nombre' => 'Nombre propio actualizado QA05',
        ], $this->token($this->candidatoUno))
            ->assertOk()->assertJsonPath('data.id', $this->perfilUno->id);

        $this->assertDatabaseHas('candidatos', [
            'id' => $this->perfilUno->id,
            'nombre' => 'Nombre propio actualizado QA05',
        ]);
        $this->assertSame($ajeno, $this->fila('candidatos', $this->perfilDos->id));
    }

    public function test_cp06_04_identificadores_ajenos_no_cambian_el_objetivo(): void
    {
        $ajeno = $this->fila('candidatos', $this->perfilDos->id);
        $usuarioAjeno = $this->fila('usuarios', $this->candidatoDos->id);

        // Politica observada en Request/Service: ignorar identificadores no validados.
        $this->api('PUT', '/api/v1/candidatos/me', [
            'id' => $this->perfilDos->id,
            'candidato_id' => $this->perfilDos->id,
            'usuario_id' => $this->candidatoDos->id,
            'rol' => 'admin',
            'rol_id' => $this->admin->rol_id,
            'nombre' => 'Actualizacion propia QA05',
        ], $this->token($this->candidatoUno))
            ->assertOk()->assertJsonPath('data.id', $this->perfilUno->id)
            ->assertJsonPath('data.usuario_id', $this->candidatoUno->id);

        $this->assertDatabaseHas('candidatos', [
            'id' => $this->perfilUno->id,
            'nombre' => 'Actualizacion propia QA05',
        ]);
        $this->assertSame('candidato', $this->candidatoUno->fresh()->rol->nombre);
        $this->assertSame($ajeno, $this->fila('candidatos', $this->perfilDos->id));
        $this->assertSame($usuarioAjeno, $this->fila('usuarios', $this->candidatoDos->id));
    }

    public function test_empresa_sin_postulacion_no_consulta_candidato_por_ruta_directa(): void
    {
        $antes = $this->fila('candidatos', $this->perfilUno->id);

        $this->api('GET', '/api/v1/candidatos/'.$this->perfilUno->id, [], $this->token($this->empresaDos))
            ->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN')
            ->assertJsonMissingPath('data.dni')->assertJsonMissingPath('data.correo');

        $this->assertSame($antes, $this->fila('candidatos', $this->perfilUno->id));
    }

    public function test_empresa_vinculada_puede_consultar_perfil_por_ruta_directa(): void
    {
        $this->postulacion();
        $this->api('GET', '/api/v1/candidatos/'.$this->perfilUno->id, [], $this->token($this->empresaUno))
            ->assertOk()->assertJsonPath('data.id', $this->perfilUno->id);
    }

    public function test_empresa_no_elude_retiro_usando_ruta_directa(): void
    {
        $postulacion = $this->postulacion();
        $postulacion->update(['retirada_en' => now()]);

        $this->api('GET', '/api/v1/candidatos/'.$this->perfilUno->id, [], $this->token($this->empresaUno))
            ->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
    }

    public function test_empresa_no_elude_vacante_eliminada_usando_ruta_directa(): void
    {
        $this->postulacion();
        $this->vacante->delete();

        $this->api('GET', '/api/v1/candidatos/'.$this->perfilUno->id, [], $this->token($this->empresaUno))
            ->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');
    }

    public function test_admin_puede_consultar_perfil_por_ruta_directa(): void
    {
        $this->api('GET', '/api/v1/candidatos/'.$this->perfilUno->id, [], $this->token($this->admin))
            ->assertOk()->assertJsonPath('data.id', $this->perfilUno->id);
    }

    public function test_cp06_03_empresa_ajena_no_consulta_perfil_de_postulacion(): void
    {
        $postulacion = $this->postulacion();
        $antes = $this->fila('postulaciones', $postulacion->id);

        $this->api('GET', '/api/v1/empresas/me/postulaciones/'.$postulacion->id.'/perfil', [], $this->token($this->empresaDos))
            ->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN')
            ->assertJsonMissingPath('data.candidato');

        $this->assertSame($antes, $this->fila('postulaciones', $postulacion->id));
    }

    public function test_empresa_duena_consulta_perfil_de_su_postulante(): void
    {
        $postulacion = $this->postulacion();

        $this->api('GET', '/api/v1/empresas/me/postulaciones/'.$postulacion->id.'/perfil', [], $this->token($this->empresaUno))
            ->assertOk()->assertJsonPath('success', true);
    }

    public function test_cp06_03_postulacion_inexistente_responde_404(): void
    {
        $idInexistente = (int) DB::table('postulaciones')->max('id') + 1000;
        $this->api('GET', '/api/v1/empresas/me/postulaciones/'.$idInexistente.'/perfil', [], $this->token($this->empresaUno))
            ->assertNotFound()->assertJsonPath('error_code', 'RESOURCE_NOT_FOUND');
    }

    public static function operacionesVacanteAjena(): array
    {
        return [
            'pausar' => ['PATCH', '/pausar'],
            'reanudar' => ['PATCH', '/reanudar'],
            'finalizar' => ['PATCH', '/finalizar'],
            'eliminar' => ['DELETE', ''],
        ];
    }

    #[DataProvider('operacionesVacanteAjena')]
    public function test_cp15_03_empresa_ajena_no_modifica_vacante(string $metodo, string $accion): void
    {
        $antes = $this->fila('empleos', $this->vacante->id);
        $this->api($metodo, '/api/v1/empresas/me/vacantes/'.$this->vacante->id.$accion, [], $this->token($this->empresaDos))
            ->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');

        $this->assertSame($antes, $this->fila('empleos', $this->vacante->id));
    }

    public function test_cp15_04_rechaza_candidato_ajeno_sin_crear_postulaciones(): void
    {
        $antes = DB::table('postulaciones')->count();
        $this->api('POST', '/api/v1/candidatos/me/postulaciones', [
            'vacante_id' => $this->vacante->id,
            'candidato_id' => $this->perfilDos->id,
        ], $this->token($this->candidatoUno))
            ->assertUnprocessable()->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors('candidato_id');

        $this->assertSame($antes, DB::table('postulaciones')->count());
        $this->assertDatabaseMissing('postulaciones', ['empleo_id' => $this->vacante->id]);
    }

    public function test_postulacion_valida_usa_identidad_del_token(): void
    {
        $this->api('POST', '/api/v1/candidatos/me/postulaciones', [
            'vacante_id' => $this->vacante->id,
        ], $this->token($this->candidatoUno))->assertCreated();

        $this->assertDatabaseHas('postulaciones', [
            'empleo_id' => $this->vacante->id,
            'candidato_id' => $this->perfilUno->id,
        ]);
        $this->assertDatabaseMissing('postulaciones', [
            'empleo_id' => $this->vacante->id,
            'candidato_id' => $this->perfilDos->id,
        ]);
    }

    public function test_cp15_01_sin_token_no_consulta_postulaciones(): void
    {
        $this->api('GET', '/api/v1/candidatos/me/postulaciones')
            ->assertUnauthorized()->assertJsonPath('error_code', 'UNAUTHENTICATED');
    }

    public function test_cp15_02_candidato_no_invoca_operacion_de_empresa(): void
    {
        $antes = $this->fila('empleos', $this->vacante->id);
        $this->api('PATCH', '/api/v1/empresas/me/vacantes/'.$this->vacante->id.'/pausar', [], $this->token($this->candidatoUno))
            ->assertForbidden()->assertJsonPath('error_code', 'FORBIDDEN');

        $this->assertSame($antes, $this->fila('empleos', $this->vacante->id));
    }
}
