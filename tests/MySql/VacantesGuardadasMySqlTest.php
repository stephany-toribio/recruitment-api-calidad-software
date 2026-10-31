<?php

namespace Tests\MySql;

use App\Models\Candidato;
use App\Models\Empleo;
use App\Models\Usuario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Testing\TestResponse;
use RuntimeException;
use Tests\TestCase;

class VacantesGuardadasMySqlTest extends TestCase
{
    private const RUTA = '/api/v1/candidatos/me/vacantes-guardadas';

    private bool $transaccionIniciada = false;

    private Candidato $candidato;

    private Candidato $otroCandidato;

    private int $empresaId;

    private int $empresaUsuarioId;

    private int $areaId;

    private int $nivelId;

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

        Carbon::setTestNow(Carbon::create(2026, 10, 25, 12, 0, 0, 'America/Lima'));

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

    public function test_guarda_una_vacante_disponible_sin_duplicar(): void
    {
        $vacante = $this->crearVacante();
        $token = $this->token($this->candidato->usuario_id);

        $this->enviar('put', $token, $vacante->getKey())
            ->assertCreated()
            ->assertJsonPath('data.vacante_id', $vacante->getKey())
            ->assertJsonPath('data.guardada', true);

        $this->enviar('put', $token, $vacante->getKey())
            ->assertOk()
            ->assertJsonPath('message', 'La vacante ya estaba guardada.');

        $this->assertSame(1, DB::table('vacantes_guardadas')
            ->where('candidato_id', $this->candidato->getKey())
            ->where('empleo_id', $vacante->getKey())
            ->count());
    }

    public function test_no_guarda_vacante_no_publicada_ni_inexistente(): void
    {
        $pendiente = $this->crearVacante(publicada: false);
        $token = $this->token($this->candidato->usuario_id);

        foreach ([$pendiente->getKey(), 999999999] as $id) {
            $this->enviar('put', $token, $id)
                ->assertNotFound()
                ->assertJsonPath('error_code', 'VACANCY_NOT_AVAILABLE');
        }

        $this->assertSame(0, DB::table('vacantes_guardadas')
            ->where('candidato_id', $this->candidato->getKey())
            ->count());
    }

    public function test_cada_candidato_ve_solo_sus_guardadas(): void
    {
        $propia = $this->crearVacante();
        $ajena = $this->crearVacante();

        $this->enviar('put', $this->token($this->candidato->usuario_id), $propia->getKey());
        $this->enviar('put', $this->token($this->otroCandidato->usuario_id), $ajena->getKey());

        $respuesta = $this->enviar('get', $this->token($this->candidato->usuario_id))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $propia->getKey())
            ->assertJsonPath('data.0.postulantes_aprox', 'Menos de 10 postulantes');

        $this->assertArrayNotHasKey('descripcion', $respuesta->json('data.0'));
    }

    public function test_lista_de_la_mas_reciente_a_la_mas_antigua(): void
    {
        $primera = $this->crearVacante();
        $segunda = $this->crearVacante();
        $token = $this->token($this->candidato->usuario_id);

        $this->enviar('put', $token, $primera->getKey());
        Carbon::setTestNow(now()->addMinute());
        $this->enviar('put', $token, $segunda->getKey());

        $ids = array_column($this->enviar('get', $token)->json('data'), 'id');

        $this->assertSame([$segunda->getKey(), $primera->getKey()], $ids);
    }

    public function test_oculta_las_que_dejan_de_estar_disponibles(): void
    {
        $pausada = $this->crearVacante();
        $eliminada = $this->crearVacante();
        $token = $this->token($this->candidato->usuario_id);

        $this->enviar('put', $token, $pausada->getKey());
        $this->enviar('put', $token, $eliminada->getKey());

        $pausada->update(['estado_empleo' => Empleo::PAUSADA]);
        $eliminada->delete();

        $this->enviar('get', $token)
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->assertSame(2, DB::table('vacantes_guardadas')
            ->where('candidato_id', $this->candidato->getKey())
            ->count());
    }

    public function test_quitar_una_vacante_es_idempotente(): void
    {
        $vacante = $this->crearVacante();
        $token = $this->token($this->candidato->usuario_id);

        $this->enviar('put', $token, $vacante->getKey());

        foreach (range(1, 2) as $intento) {
            $this->enviar('delete', $token, $vacante->getKey())
                ->assertOk()
                ->assertJsonPath('data.guardada', false);
        }

        $this->assertSame(0, DB::table('vacantes_guardadas')
            ->where('candidato_id', $this->candidato->getKey())
            ->count());
    }

    public function test_solo_un_candidato_autenticado_accede(): void
    {
        $vacante = $this->crearVacante();
        $tokenEmpresa = $this->token($this->empresaUsuarioId);

        foreach (['get', 'put', 'delete'] as $metodo) {
            $id = $metodo === 'get' ? null : $vacante->getKey();

            $this->enviar($metodo, null, $id)
                ->assertUnauthorized()
                ->assertJsonPath('error_code', 'UNAUTHENTICATED');

            $this->enviar($metodo, $tokenEmpresa, $id)
                ->assertForbidden()
                ->assertJsonPath('error_code', 'FORBIDDEN');
        }

        $this->assertSame(0, DB::table('vacantes_guardadas')->count());
    }

    private function enviar(string $metodo, ?string $token, ?int $vacanteId = null): TestResponse
    {
        $this->app->make('auth')->forgetGuards();
        $this->withoutHeader('Authorization');

        $ruta = $vacanteId === null ? self::RUTA : self::RUTA.'/'.$vacanteId;
        $cabeceras = $token === null ? [] : ['Authorization' => 'Bearer '.$token];

        return $this->json(strtoupper($metodo), $ruta, [], $cabeceras);
    }

    private function token(int $usuarioId): string
    {
        return Usuario::findOrFail($usuarioId)
            ->createToken('prueba-guardadas')
            ->plainTextToken;
    }

    private function prepararDatos(): void
    {
        $this->candidato = $this->crearCandidato();
        $this->otroCandidato = $this->crearCandidato();
        $this->empresaUsuarioId = $this->crearUsuario('empresa');

        $this->empresaId = DB::table('empresas')->insertGetId([
            'usuario_id' => $this->empresaUsuarioId,
            'encargado_empresa' => 'Responsable guardadas',
            'razon_social' => 'Empresa guardadas '.bin2hex(random_bytes(6)),
            'sector' => 'Tecnología e Informática',
            'descripcion' => null,
            'ruc' => '20'.random_int(100000000, 999999999),
            'sitio_web' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->areaId = DB::table('areas_estudio')->insertGetId([
            'nombre' => 'Área guardadas '.bin2hex(random_bytes(6)),
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
            throw new RuntimeException('Falta el nivel global activo Junior.');
        }

        $this->nivelId = (int) $nivelId;
    }

    private function crearVacante(bool $publicada = true): Empleo
    {
        $vacante = Empleo::create([
            'empresa_id' => $this->empresaId,
            'titulo' => 'Vacante guardada '.bin2hex(random_bytes(4)),
            'descripcion' => 'Vacante para comprobar las vacantes guardadas por los candidatos.',
            'nivel_puesto_id' => $this->nivelId,
            'area_estudio_id' => $this->areaId,
            'tipo_empleo' => 'remoto',
            'ubicacion' => null,
            'salario_min' => null,
            'salario_max' => null,
            'fecha_inicio_vigencia' => '2026-10-25',
            'fecha_fin_vigencia' => '2026-10-30',
            'estado_empleo' => Empleo::ACTIVA,
            'numero_vacantes' => 1,
        ]);

        if ($publicada) {
            DB::table('revisiones_vacante')->insert([
                'empleo_id' => $vacante->getKey(),
                'admin_id' => $this->crearUsuario('admin'),
                'decision' => 'aprobada',
                'motivo' => null,
                'invalidada_en' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('pagos_vacante')->insert([
                'empleo_id' => $vacante->getKey(),
                'resultado' => 'exitoso',
                'monto' => 100,
                'moneda' => 'PEN',
                'tarjeta_ultimos4' => '1111',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $vacante;
    }

    private function crearUsuario(string $rol): int
    {
        $rolId = DB::table('roles')->where('nombre', $rol)->value('id')
            ?? DB::table('roles')->insertGetId([
                'nombre' => $rol,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

        return (int) DB::table('usuarios')->insertGetId([
            'rol_id' => $rolId,
            'correo' => 'guardadas-'.bin2hex(random_bytes(10)).'@example.test',
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
            'nombre' => 'Candidato',
            'apellidos' => 'Guardadas',
            'dni' => null,
            'fecha_nacimiento' => '2000-01-01',
            'genero' => null,
            'busca_primer_empleo' => true,
            'celular' => '9'.str_pad((string) $usuarioId, 8, '0', STR_PAD_LEFT),
        ]);
    }
}
