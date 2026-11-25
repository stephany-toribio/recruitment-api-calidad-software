<?php

namespace Tests\MySql;

use App\Http\Resources\Api\V1\VacanteBusquedaResource;
use App\Models\Empleo;
use App\Models\Usuario;
use App\Services\Vacantes\AproximacionPostulantes;
use App\Services\Vacantes\BusquedaVacantesService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class BusquedaVacantesMySqlTest extends TestCase
{
    private int $empresaId;

    private int $adminId;

    private int $juniorId;

    private int $seniorId;

    private int $areaUnoId;

    private int $areaDosId;

    private int $pythonId;

    private int $sqlId;

    private int $otraHabilidadId;

    private array $vacantes = [];

    private bool $transaccionIniciada = false;

    protected function setUp(): void
    {
        parent::setUp();

        $this->assertFalse($this->app->configurationIsCached());
        $this->assertTrue($this->app->environment('testing'));
        $this->assertSame('mysql', DB::connection()->getDriverName());
        $this->assertSame(
            'portalempleoshu04testing',
            DB::selectOne('SELECT DATABASE() AS nombre')->nombre
        );

        Carbon::setTestNow(
            Carbon::parse('2026-10-24 12:00:00', 'America/Lima')
        );

        DB::beginTransaction();
        $this->transaccionIniciada = true;

        try {
            $etiqueta = bin2hex(random_bytes(6));

            $usuarioEmpresaId = $this->crearUsuario('empresa');
            $this->adminId = $this->crearUsuario('admin');

            $this->empresaId = DB::table('empresas')->insertGetId([
                'usuario_id' => $usuarioEmpresaId,
                'encargado_empresa' => 'Responsable de prueba',
                'razon_social' => 'Empresa búsqueda '.$etiqueta,
                'sector' => 'Tecnología e Informática',
                'ruc' => '20'.random_int(100000000, 999999999),
                'sitio_web' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->juniorId = $this->obtenerNivel('Junior');
            $this->seniorId = $this->obtenerNivel('Senior');

            $this->areaUnoId = $this->crearArea('Área uno '.$etiqueta);
            $this->areaDosId = $this->crearArea('Área dos '.$etiqueta);

            $this->pythonId = $this->crearHabilidad(
                'Python '.$etiqueta
            );
            $this->sqlId = $this->crearHabilidad(
                'SQL '.$etiqueta
            );
            $this->otraHabilidadId = $this->crearHabilidad(
                'Otra habilidad '.$etiqueta
            );

            $this->vacantes[] = $this->crearVacante([
                'titulo' => 'Analista Python',
                'nivel_puesto_id' => $this->juniorId,
                'area_estudio_id' => $this->areaUnoId,
                'tipo_empleo' => 'remoto',
                'ubicacion' => null,
                'salario_min' => '2000.00',
                'salario_max' => '4000.00',
            ]);

            $this->vacantes[] = $this->crearVacante([
                'titulo' => 'Analista SQL',
                'nivel_puesto_id' => $this->seniorId,
                'area_estudio_id' => $this->areaDosId,
                'tipo_empleo' => 'hibrido',
                'ubicacion' => 'Lima',
                'salario_min' => '2000.00',
                'salario_max' => '2800.00',
            ]);

            $this->vacantes[] = $this->crearVacante([
                'titulo' => 'Diseñador de productos',
                'nivel_puesto_id' => $this->juniorId,
                'area_estudio_id' => $this->areaUnoId,
                'tipo_empleo' => 'presencial',
                'ubicacion' => 'Arequipa',
                'salario_min' => null,
                'salario_max' => null,
            ]);

            $this->vacantes[0]->habilidades()->attach(
                $this->pythonId,
                ['tipo_requisito' => 'must_have']
            );

            $this->vacantes[1]->habilidades()->attach(
                $this->sqlId,
                ['tipo_requisito' => 'must_have']
            );

            $this->vacantes[2]->habilidades()->attach(
                $this->pythonId,
                ['tipo_requisito' => 'nice_to_have']
            );
        } catch (\Throwable $error) {
            DB::rollBack();
            $this->transaccionIniciada = false;
            Carbon::setTestNow();

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
            Carbon::setTestNow();

            parent::tearDown();
        }
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

        return DB::table('usuarios')->insertGetId([
            'rol_id' => $rolId,
            'correo' => bin2hex(random_bytes(12)).'@example.test',
            'password' => Hash::make(bin2hex(random_bytes(24))),
            'estado_cuenta' => 'activo',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function obtenerNivel(string $nombre): int
    {
        $id = DB::table('niveles_puesto')
            ->whereNull('candidato_id')
            ->where('estado', 'activo')
            ->where('nombre', $nombre)
            ->value('id');

        $this->assertNotNull($id);

        return (int) $id;
    }

    private function crearArea(string $nombre): int
    {
        return DB::table('areas_estudio')->insertGetId([
            'nombre' => $nombre,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearHabilidad(string $nombre): int
    {
        return DB::table('habilidades')->insertGetId([
            'nombre' => $nombre,
            'tipo_habilidad' => 'tecnica',
            'area_estudio_id' => $this->areaUnoId,
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function crearVacante(array $atributos): Empleo
    {
        $vacante = Empleo::create(array_merge([
            'empresa_id' => $this->empresaId,
            'descripcion' => str_repeat('Descripción de prueba. ', 4),
            'fecha_inicio_vigencia' => '2026-10-24',
            'fecha_fin_vigencia' => '2026-10-25',
            'estado_empleo' => 'activa',
            'numero_vacantes' => 2,
        ], $atributos));

        DB::table('revisiones_vacante')->insert([
            'empleo_id' => $vacante->id,
            'admin_id' => $this->adminId,
            'decision' => 'aprobada',
            'motivo' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('pagos_vacante')->insert([
            'empleo_id' => $vacante->id,
            'resultado' => 'exitoso',
            'monto' => '200.00',
            'moneda' => 'PEN',
            'tarjeta_ultimos4' => '1111',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $vacante;
    }

    private function comprobarResultados(
        array $filtros,
        array $posicionesEsperadas
    ): void {
        $idsPreparados = array_map(
            fn (Empleo $vacante): int => (int) $vacante->id,
            $this->vacantes
        );

        $esperados = array_map(
            fn (int $posicion): int => (int) $this->vacantes[$posicion]->id,
            $posicionesEsperadas
        );

        $obtenidos = $this->app
            ->make(BusquedaVacantesService::class)
            ->construirConsulta($filtros)
            ->whereIn('empleos.id', $idsPreparados)
            ->pluck('empleos.id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        sort($esperados);
        sort($obtenidos);

        $this->assertSame($esperados, $obtenidos);
    }

    public function test_sin_filtros_incluye_vacantes_con_y_sin_salario(): void
    {
        $this->comprobarResultados([], [0, 1, 2]);
    }

    public function test_titulo_parcial_no_distingue_mayusculas(): void
    {
        $this->comprobarResultados(
            ['titulo' => 'aNaLiStA'],
            [0, 1]
        );
    }

    public function test_busca_solo_en_titulo(): void
    {
        $this->comprobarResultados(
            ['titulo' => 'Descripción de prueba'],
            []
        );
    }

    #[DataProvider('caracteresLiterales')]
    public function test_caracteres_especiales_se_buscan_literalmente(
        string $caracter
    ): void {
        $this->vacantes[0]->update([
            'titulo' => 'Analista '.$caracter.' especializado',
        ]);

        $this->comprobarResultados(
            ['titulo' => $caracter],
            [0]
        );
    }

    public static function caracteresLiterales(): array
    {
        return [
            'porcentaje' => ['%'],
            'guion bajo' => ['_'],
            'signo de admiracion' => ['!'],
        ];
    }

    public function test_texto_sql_no_altera_la_consulta(): void
    {
        $this->comprobarResultados(
            ['titulo' => "' OR 1=1 --"],
            []
        );
    }

    public function test_habilidad_coincide_como_obligatoria_o_deseable(): void
    {
        $this->comprobarResultados(
            ['habilidades' => [$this->pythonId]],
            [0, 2]
        );
    }

    public function test_varias_habilidades_se_combinan_con_o(): void
    {
        $this->comprobarResultados(
            ['habilidades' => [$this->pythonId, $this->sqlId]],
            [0, 1, 2]
        );
    }

    public function test_habilidad_sin_vacantes_devuelve_vacio(): void
    {
        $this->comprobarResultados(
            ['habilidades' => [$this->otraHabilidadId]],
            []
        );
    }

    public function test_coincidir_con_dos_habilidades_no_duplica_vacante(): void
    {
        $this->vacantes[0]->habilidades()->attach(
            $this->sqlId,
            ['tipo_requisito' => 'nice_to_have']
        );

        $this->comprobarResultados(
            ['habilidades' => [$this->pythonId, $this->sqlId]],
            [0, 1, 2]
        );
    }

    public function test_filtra_un_nivel(): void
    {
        $this->comprobarResultados(
            ['niveles_puesto' => [$this->juniorId]],
            [0, 2]
        );
    }

    public function test_varios_niveles_se_combinan_con_o(): void
    {
        $this->comprobarResultados(
            ['niveles_puesto' => [$this->juniorId, $this->seniorId]],
            [0, 1, 2]
        );
    }

    public function test_filtra_por_area_profesional(): void
    {
        $this->comprobarResultados(
            ['area_estudio_id' => $this->areaUnoId],
            [0, 2]
        );
    }

    public function test_solo_remoto_excluye_hibrido_y_presencial(): void
    {
        $this->comprobarResultados(
            ['solo_remoto' => '1'],
            [0]
        );
    }

    public function test_remoto_apagado_no_restringe_modalidad(): void
    {
        $this->comprobarResultados(
            ['solo_remoto' => '0'],
            [0, 1, 2]
        );
    }

    #[DataProvider('minimosSalariales')]
    public function test_salario_compara_maximo_y_excluye_importes_ausentes(
        int $minimo,
        array $posicionesEsperadas
    ): void {
        $this->comprobarResultados(
            ['salario_minimo' => $minimo],
            $posicionesEsperadas
        );
    }

    public static function minimosSalariales(): array
    {
        return [
            '1500 incluye ambos rangos' => [1500, [0, 1]],
            '3000 incluye rango cuyo maximo es 4000' => [3000, [0]],
            '5000 no tiene coincidencias' => [5000, []],
            '7000 no tiene coincidencias' => [7000, []],
            '9000 no tiene coincidencias' => [9000, []],
        ];
    }

    public function test_salario_maximo_igual_al_filtro_se_incluye(): void
    {
        $this->vacantes[0]->update([
            'salario_max' => '3000.00',
        ]);

        $this->comprobarResultados(
            ['salario_minimo' => 3000],
            [0]
        );
    }

    public function test_filtros_distintos_se_combinan_con_y(): void
    {
        $this->comprobarResultados([
            'titulo' => 'Analista',
            'habilidades' => [$this->pythonId, $this->sqlId],
            'niveles_puesto' => [$this->juniorId],
            'area_estudio_id' => $this->areaUnoId,
            'solo_remoto' => '1',
            'salario_minimo' => 3000,
        ], [0]);
    }

    public function test_filtros_incompatibles_devuelven_vacio(): void
    {
        $this->comprobarResultados([
            'niveles_puesto' => [$this->seniorId],
            'solo_remoto' => '1',
        ], []);
    }

    public function test_vacante_pausada_no_aparece_aunque_coincida(): void
    {
        DB::table('empleos')
            ->where('id', $this->vacantes[0]->id)
            ->update(['estado_empleo' => 'pausada']);

        $this->comprobarResultados(
            ['titulo' => 'Analista'],
            [1]
        );
    }

    private function prepararGrupoPaginado(int $cantidad = 3): string
    {
        $titulo = 'GrupoHU04-'.$this->vacantes[0]->id;

        foreach ($this->vacantes as $vacante) {
            $vacante->update(['titulo' => $titulo]);
        }

        for ($indice = count($this->vacantes); $indice < $cantidad; $indice++) {
            $this->vacantes[] = $this->crearVacante([
                'titulo' => $titulo,
                'nivel_puesto_id' => $this->juniorId,
                'area_estudio_id' => $this->areaUnoId,
                'tipo_empleo' => 'remoto',
                'ubicacion' => null,
                'salario_min' => null,
                'salario_max' => null,
            ]);
        }

        return $titulo;
    }

    private function idsDePagina(
        LengthAwarePaginator $pagina
    ): array {
        return array_map(
            fn (Empleo $vacante): int => (int) $vacante->id,
            $pagina->items()
        );
    }

    public function test_pagina_23_coincidencias_sin_repetir_resultados(): void
    {
        $titulo = $this->prepararGrupoPaginado(23);
        $servicio = $this->app->make(BusquedaVacantesService::class);

        $obtenidos = [];

        foreach ([1 => 10, 2 => 10, 3 => 3] as $numero => $cantidad) {
            $pagina = $servicio->buscar([
                'titulo' => $titulo,
                'page' => $numero,
            ]);

            $this->assertSame($numero, $pagina->currentPage());
            $this->assertSame(10, $pagina->perPage());
            $this->assertSame(23, $pagina->total());
            $this->assertSame(3, $pagina->lastPage());
            $this->assertCount($cantidad, $pagina->items());

            $obtenidos = array_merge(
                $obtenidos,
                $this->idsDePagina($pagina)
            );
        }

        $esperados = array_map(
            fn (Empleo $vacante): int => (int) $vacante->id,
            $this->vacantes
        );

        rsort($esperados);

        $this->assertSame($esperados, $obtenidos);
        $this->assertCount(23, array_unique($obtenidos));
    }

    public function test_ordena_por_publicacion_y_no_por_creacion(): void
    {
        $titulo = $this->prepararGrupoPaginado();

        $fechasPublicacion = [
            '2026-10-24 11:00:00',
            '2026-10-24 09:00:00',
            '2026-10-24 10:00:00',
        ];

        $fechasCreacion = [
            '2026-10-24 06:00:00',
            '2026-10-24 08:00:00',
            '2026-10-24 07:00:00',
        ];

        foreach ($this->vacantes as $indice => $vacante) {
            DB::table('empleos')
                ->where('id', $vacante->id)
                ->update(['created_at' => $fechasCreacion[$indice]]);

            DB::table('pagos_vacante')
                ->where('empleo_id', $vacante->id)
                ->where('resultado', 'exitoso')
                ->update(['created_at' => $fechasPublicacion[$indice]]);
        }

        $pagina = $this->app
            ->make(BusquedaVacantesService::class)
            ->buscar(['titulo' => $titulo]);

        $this->assertSame([
            (int) $this->vacantes[0]->id,
            (int) $this->vacantes[2]->id,
            (int) $this->vacantes[1]->id,
        ], $this->idsDePagina($pagina));
    }

    public function test_publicaciones_iguales_desempatan_por_id_descendente(): void
    {
        $titulo = $this->prepararGrupoPaginado();

        $pagina = $this->app
            ->make(BusquedaVacantesService::class)
            ->buscar(['titulo' => $titulo]);

        $this->assertSame([
            (int) $this->vacantes[2]->id,
            (int) $this->vacantes[1]->id,
            (int) $this->vacantes[0]->id,
        ], $this->idsDePagina($pagina));
    }

    public function test_varios_pagos_no_duplican_y_conservan_primera_publicacion(): void
    {
        $titulo = $this->prepararGrupoPaginado();

        foreach ($this->vacantes as $indice => $vacante) {
            DB::table('pagos_vacante')
                ->where('empleo_id', $vacante->id)
                ->where('resultado', 'exitoso')
                ->update([
                    'created_at' => [
                        '2026-10-24 09:00:00',
                        '2026-10-24 10:00:00',
                        '2026-10-24 11:00:00',
                    ][$indice],
                ]);
        }

        foreach (['exitoso', 'fallido'] as $resultado) {
            DB::table('pagos_vacante')->insert([
                'empleo_id' => $this->vacantes[0]->id,
                'resultado' => $resultado,
                'monto' => '200.00',
                'moneda' => 'PEN',
                'tarjeta_ultimos4' => '1111',
                'created_at' => '2026-10-24 12:00:00',
                'updated_at' => '2026-10-24 12:00:00',
            ]);
        }

        $pagina = $this->app
            ->make(BusquedaVacantesService::class)
            ->buscar(['titulo' => $titulo]);

        $this->assertSame(3, $pagina->total());
        $this->assertCount(3, $pagina->items());

        $this->assertSame([
            (int) $this->vacantes[2]->id,
            (int) $this->vacantes[1]->id,
            (int) $this->vacantes[0]->id,
        ], $this->idsDePagina($pagina));
    }

    public function test_total_y_paginas_se_calculan_despues_de_filtrar(): void
    {
        $titulo = $this->prepararGrupoPaginado(23);

        $pagina = $this->app
            ->make(BusquedaVacantesService::class)
            ->buscar([
                'titulo' => $titulo,
                'niveles_puesto' => [$this->seniorId],
            ]);

        $this->assertSame(1, $pagina->total());
        $this->assertSame(1, $pagina->lastPage());
        $this->assertSame([
            (int) $this->vacantes[1]->id,
        ], $this->idsDePagina($pagina));
    }

    public function test_sin_coincidencias_devuelve_paginacion_vacia(): void
    {
        $titulo = $this->prepararGrupoPaginado();

        $pagina = $this->app
            ->make(BusquedaVacantesService::class)
            ->buscar([
                'titulo' => $titulo,
                'salario_minimo' => 9000,
            ]);

        $this->assertSame(1, $pagina->currentPage());
        $this->assertSame(10, $pagina->perPage());
        $this->assertSame(0, $pagina->total());
        $this->assertSame(1, $pagina->lastPage());
        $this->assertSame([], $pagina->items());
    }

    public function test_pagina_fuera_del_rango_conserva_total_y_devuelve_vacio(): void
    {
        $titulo = $this->prepararGrupoPaginado(23);

        $pagina = $this->app
            ->make(BusquedaVacantesService::class)
            ->buscar([
                'titulo' => $titulo,
                'page' => 4,
            ]);

        $this->assertSame(4, $pagina->currentPage());
        $this->assertSame(10, $pagina->perPage());
        $this->assertSame(23, $pagina->total());
        $this->assertSame(3, $pagina->lastPage());
        $this->assertSame([], $pagina->items());
    }

    public function test_tamano_de_pagina_permanece_fijo(): void
    {
        $titulo = $this->prepararGrupoPaginado(23);

        $pagina = $this->app
            ->make(BusquedaVacantesService::class)
            ->buscar([
                'titulo' => $titulo,
                'per_page' => 100,
            ]);

        $this->assertSame(10, $pagina->perPage());
        $this->assertCount(10, $pagina->items());
        $this->assertSame(23, $pagina->total());
    }

    private function crearPostulaciones(
        int $cantidad,
        int $empleoId
    ): void {
        $nombres = [
            'Recibido',
            'En Revisión',
            'Entrevista',
            'Contratado',
            'Rechazado',
        ];

        $estados = [];

        foreach ($nombres as $nombre) {
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

            $estados[] = (int) $id;
        }

        for ($indice = 0; $indice < $cantidad; $indice++) {
            $usuarioId = $this->crearUsuario('candidato');

            $candidatoId = DB::table('candidatos')->insertGetId([
                'usuario_id' => $usuarioId,
                'nombre' => 'Candidato de prueba',
                'apellidos' => 'Conteo HU04',
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
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::table('postulaciones')->insert([
                'candidato_id' => $candidatoId,
                'empleo_id' => $empleoId,
                'estado_postulacion_id' => $estados[
                    $indice % count($estados)
                ],
                'porcentaje_match' => null,
                'fecha_postulacion' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    #[DataProvider('rangosDePostulaciones')]
    public function test_cuenta_postulaciones_reales_y_obtiene_el_rango(
        int $cantidad,
        string $textoEsperado
    ): void {
        $titulo = $this->prepararGrupoPaginado();

        // Mantener la vacante disponible durante la prueba de conteo.
        $this->vacantes[0]->update([
            'numero_vacantes' => $cantidad + 1,
        ]);

        $this->crearPostulaciones(
            $cantidad,
            (int) $this->vacantes[0]->id
        );

        $pagina = $this->app
            ->make(BusquedaVacantesService::class)
            ->buscar(['titulo' => $titulo]);

        $vacante = $pagina->getCollection()->firstWhere(
            'id',
            $this->vacantes[0]->id
        );

        $this->assertNotNull($vacante);

        $this->assertSame(
            $cantidad,
            (int) $vacante->postulaciones_count
        );

        $this->assertSame(
            $textoEsperado,
            AproximacionPostulantes::texto(
                (int) $vacante->postulaciones_count
            )
        );

        $this->assertSame(3, $pagina->total());
        $this->assertCount(3, $pagina->items());
    }

    public static function rangosDePostulaciones(): array
    {
        return [
            'cero' => [0, 'Menos de 10 postulantes'],
            'uno' => [1, 'Menos de 10 postulantes'],
            'nueve' => [9, 'Menos de 10 postulantes'],
            'diez' => [10, 'Entre 10 y 24 postulantes'],
            'veinticuatro' => [24, 'Entre 10 y 24 postulantes'],
            'veinticinco' => [25, 'Entre 25 y 49 postulantes'],
            'cuarenta y nueve' => [49, 'Entre 25 y 49 postulantes'],
            'cincuenta' => [50, 'Entre 50 y 99 postulantes'],
            'noventa y nueve' => [99, 'Entre 50 y 99 postulantes'],
            'cien' => [100, '100 o más postulantes'],
        ];
    }

    public function test_cuenta_todos_los_estados_sin_mezclar_vacantes(): void
    {
        $titulo = $this->prepararGrupoPaginado();

        $this->crearPostulaciones(
            5,
            (int) $this->vacantes[0]->id
        );

        $this->crearPostulaciones(
            2,
            (int) $this->vacantes[1]->id
        );

        $estadosDistintos = DB::table('postulaciones')
            ->where('empleo_id', $this->vacantes[0]->id)
            ->distinct()
            ->count('estado_postulacion_id');

        $this->assertSame(5, $estadosDistintos);

        $pagina = $this->app
            ->make(BusquedaVacantesService::class)
            ->buscar(['titulo' => $titulo]);

        $porId = $pagina->getCollection()->keyBy('id');

        $this->assertSame(
            5,
            (int) $porId[$this->vacantes[0]->id]->postulaciones_count
        );

        $this->assertSame(
            2,
            (int) $porId[$this->vacantes[1]->id]->postulaciones_count
        );

        $this->assertSame(
            0,
            (int) $porId[$this->vacantes[2]->id]->postulaciones_count
        );

        $this->assertSame(3, $pagina->total());

        foreach ($pagina->items() as $vacante) {
            $this->assertFalse(
                $vacante->relationLoaded('postulaciones')
            );
        }
    }

    public function test_carga_las_relaciones_necesarias_para_las_tarjetas(): void
    {
        $titulo = $this->prepararGrupoPaginado();

        $pagina = $this->app
            ->make(BusquedaVacantesService::class)
            ->buscar(['titulo' => $titulo]);

        foreach ($pagina->items() as $vacante) {
            foreach ([
                'empresa',
                'nivelPuesto',
                'areaEstudio',
                'habilidades',
            ] as $relacion) {
                $this->assertTrue(
                    $vacante->relationLoaded($relacion),
                    "Falta cargar la relacion {$relacion}."
                );
            }

            $this->assertNotNull($vacante->empresa);
            $this->assertNotNull($vacante->nivelPuesto);
            $this->assertNotNull($vacante->areaEstudio);
        }
    }

    #[DataProvider('rangosDePostulaciones')]
    public function test_recurso_publico_entrega_la_aproximacion(
        int $cantidad,
        string $textoEsperado
    ): void {
        $titulo = $this->prepararGrupoPaginado();

        // Mantener la vacante disponible durante la prueba de conteo.
        $this->vacantes[0]->update([
            'numero_vacantes' => $cantidad + 1,
        ]);

        $this->crearPostulaciones(
            $cantidad,
            (int) $this->vacantes[0]->id
        );

        $pagina = $this->app
            ->make(BusquedaVacantesService::class)
            ->buscar(['titulo' => $titulo]);

        $vacante = $pagina->getCollection()->firstWhere(
            'id',
            $this->vacantes[0]->id
        );

        $this->assertNotNull($vacante);

        $datos = (new VacanteBusquedaResource(
            $vacante
        ))->resolve(
            Request::create('/api/v1/vacantes', 'GET')
        );

        $this->assertSame(
            $textoEsperado,
            $datos['postulantes_aprox']
        );

        $this->assertArrayNotHasKey('postulaciones_count', $datos);
        $this->assertArrayNotHasKey('postulaciones', $datos);
        $this->assertArrayNotHasKey('porcentaje_match', $datos);
    }

    public function test_recurso_publico_expone_solo_los_campos_acordados(): void
    {
        $titulo = $this->prepararGrupoPaginado();

        $pagina = $this->app
            ->make(BusquedaVacantesService::class)
            ->buscar(['titulo' => $titulo]);

        $vacante = $pagina->getCollection()->firstWhere(
            'id',
            $this->vacantes[0]->id
        );

        $this->assertNotNull($vacante);

        // Aunque se carguen datos internos, no deben aparecer en el JSON.
        $vacante->load(['empresa.usuario', 'pagos', 'revision']);

        $datos = (new VacanteBusquedaResource(
            $vacante
        ))->resolve(
            Request::create('/api/v1/vacantes', 'GET')
        );

        $this->assertSame([
            'id',
            'titulo',
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
        ], array_keys($datos));

        $this->assertSame(
            ['razon_social', 'sector'],
            array_keys($datos['empresa'])
        );

        $this->assertSame(
            ['id', 'nombre'],
            array_keys($datos['nivel_puesto'])
        );

        $this->assertSame(
            ['id', 'nombre'],
            array_keys($datos['area_profesional'])
        );

        $this->assertSame('remoto', $datos['tipo_empleo']);
        $this->assertNull($datos['ubicacion']);
        $this->assertSame(2000.0, $datos['salario_min']);
        $this->assertSame(4000.0, $datos['salario_max']);
        $this->assertSame('2026-10-24', $datos['fecha_inicio_vigencia']);
        $this->assertSame('2026-10-25', $datos['fecha_fin_vigencia']);

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+00:00$/',
            $datos['publicada_en']
        );

        $this->assertCount(1, $datos['habilidades']);

        $this->assertSame([
            'id',
            'nombre',
            'tipo_habilidad',
        ], array_keys($datos['habilidades'][0]));

        $this->assertSame(
            $this->pythonId,
            $datos['habilidades'][0]['id']
        );
    }

    public function test_recurso_conserva_salarios_ausentes_como_null(): void
    {
        $titulo = $this->prepararGrupoPaginado();

        $pagina = $this->app
            ->make(BusquedaVacantesService::class)
            ->buscar(['titulo' => $titulo]);

        $vacante = $pagina->getCollection()->firstWhere(
            'id',
            $this->vacantes[2]->id
        );

        $this->assertNotNull($vacante);

        $datos = (new VacanteBusquedaResource(
            $vacante
        ))->resolve(
            Request::create('/api/v1/vacantes', 'GET')
        );

        $this->assertNull($datos['salario_min']);
        $this->assertNull($datos['salario_max']);
        $this->assertSame('Arequipa', $datos['ubicacion']);

        $this->assertArrayNotHasKey(
            'requisito',
            $datos['habilidades'][0]
        );
    }

    private function prepararAutorizacionHttp(?string $token): void
    {
        $this->app->make('auth')->forgetGuards();
        $this->withoutHeader('Authorization');

        if ($token !== null) {
            $this->withToken($token);
        }
    }

    private function crearUsuarioHttp(
        string $rol = 'candidato'
    ): Usuario {
        $usuarioId = $this->crearUsuario($rol);

        if ($rol === 'candidato') {
            DB::table('candidatos')->insert([
                'usuario_id' => $usuarioId,
                'nombre' => 'Candidato HTTP',
                'apellidos' => 'Prueba HU04',
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
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return Usuario::findOrFail($usuarioId);
    }

    private function autenticarCandidatoHttp(): Usuario
    {
        $usuario = $this->crearUsuarioHttp();

        $this->prepararAutorizacionHttp(
            $usuario->createToken('prueba-hu04')->plainTextToken
        );

        return $usuario;
    }

    private function urlBusquedaHttp(array $filtros = []): string
    {
        $query = http_build_query($filtros);

        return '/api/v1/vacantes'.($query === '' ? '' : '?'.$query);
    }

    public function test_http_candidato_activo_recibe_lista_y_meta(): void
    {
        $titulo = $this->prepararGrupoPaginado();
        $this->autenticarCandidatoHttp();

        $respuesta = $this->getJson(
            $this->urlBusquedaHttp(['titulo' => $titulo])
        );

        $respuesta
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Vacantes encontradas')
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath('meta.last_page', 1);

        $this->assertSame(
            ['success', 'message', 'data', 'meta'],
            array_keys($respuesta->json())
        );

        $this->assertSame(
            ['current_page', 'per_page', 'total', 'last_page'],
            array_keys($respuesta->json('meta'))
        );
    }

    public function test_http_sin_token_responde_401_antes_de_validar(): void
    {
        $this->prepararAutorizacionHttp(null);

        $this->getJson(
            $this->urlBusquedaHttp(['page' => 0])
        )
            ->assertUnauthorized()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');
    }

    public function test_http_token_alterado_responde_401(): void
    {
        $usuario = $this->crearUsuarioHttp();
        $token = $usuario->createToken('prueba-hu04')->plainTextToken;

        $this->prepararAutorizacionHttp($token.'alterado');

        $this->getJson('/api/v1/vacantes')
            ->assertUnauthorized()
            ->assertJsonPath('error_code', 'UNAUTHENTICATED');
    }

    #[DataProvider('rolesSinAccesoHttp')]
    public function test_http_otro_rol_recibe_403(string $rol): void
    {
        $usuario = $this->crearUsuarioHttp($rol);

        $this->prepararAutorizacionHttp(
            $usuario->createToken('prueba-hu04')->plainTextToken
        );

        $this->getJson('/api/v1/vacantes')
            ->assertForbidden()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'FORBIDDEN');
    }

    public static function rolesSinAccesoHttp(): array
    {
        return [
            'empresa' => ['empresa'],
            'administrador' => ['admin'],
        ];
    }

    public function test_http_suspension_directa_bloquea_token_existente(): void
    {
        $usuario = $this->autenticarCandidatoHttp();

        DB::table('usuarios')
            ->where('id', $usuario->id)
            ->update(['estado_cuenta' => 'suspendido']);

        $this->getJson('/api/v1/vacantes')
            ->assertForbidden()
            ->assertJsonPath('success', false);
    }

    public function test_http_token_revocado_responde_401(): void
    {
        $usuario = $this->autenticarCandidatoHttp();

        $usuario->tokens()->delete();

        $this->getJson('/api/v1/vacantes')
            ->assertUnauthorized();
    }

    public function test_http_token_expirado_responde_401(): void
    {
        $usuario = $this->crearUsuarioHttp();

        $token = $usuario->createToken(
            'prueba-hu04',
            ['*'],
            now()->subMinute()
        );

        $this->prepararAutorizacionHttp($token->plainTextToken);

        $this->getJson('/api/v1/vacantes')
            ->assertUnauthorized();
    }

    #[DataProvider('filtrosInvalidosHttp')]
    public function test_http_filtros_invalidos_responden_422(
        array $filtros,
        string $campo
    ): void {
        $this->autenticarCandidatoHttp();

        $this->getJson($this->urlBusquedaHttp($filtros))
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors([$campo]);
    }

    public static function filtrosInvalidosHttp(): array
    {
        return [
            'titulo demasiado largo' => [
                ['titulo' => str_repeat('a', 101)],
                'titulo',
            ],
            'pagina cero' => [
                ['page' => 0],
                'page',
            ],
            'pagina como lista' => [
                ['page' => [1]],
                'page',
            ],
            'remoto como texto true' => [
                ['solo_remoto' => 'true'],
                'solo_remoto',
            ],
            'salario fuera del selector' => [
                ['salario_minimo' => 2000],
                'salario_minimo',
            ],
            'niveles como texto' => [
                ['niveles_puesto' => 'Junior'],
                'niveles_puesto',
            ],
            'area cero' => [
                ['area_estudio_id' => 0],
                'area_estudio_id',
            ],
            'habilidades como texto' => [
                ['habilidades' => 'Python'],
                'habilidades',
            ],
        ];
    }

    public function test_http_habilidades_duplicadas_responden_422(): void
    {
        $this->autenticarCandidatoHttp();

        $this->getJson($this->urlBusquedaHttp([
            'habilidades' => [$this->pythonId, $this->pythonId],
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['habilidades.0']);
    }

    public function test_http_filtros_combinados_devuelven_la_vacante_correcta(): void
    {
        $titulo = $this->prepararGrupoPaginado();
        $this->autenticarCandidatoHttp();

        $this->getJson($this->urlBusquedaHttp([
            'titulo' => $titulo,
            'habilidades' => [$this->pythonId, $this->sqlId],
            'niveles_puesto' => [$this->juniorId],
            'area_estudio_id' => $this->areaUnoId,
            'solo_remoto' => 1,
            'salario_minimo' => 3000,
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', (int) $this->vacantes[0]->id)
            ->assertJsonPath('meta.total', 1);
    }

    public function test_http_pagina_dos_conserva_filtros_y_total(): void
    {
        $titulo = $this->prepararGrupoPaginado(23);
        $this->autenticarCandidatoHttp();

        $respuesta = $this->getJson($this->urlBusquedaHttp([
            'titulo' => $titulo,
            'page' => 2,
        ]));

        $respuesta
            ->assertOk()
            ->assertJsonCount(10, 'data')
            ->assertJsonPath('meta.current_page', 2)
            ->assertJsonPath('meta.per_page', 10)
            ->assertJsonPath('meta.total', 23)
            ->assertJsonPath('meta.last_page', 3);

        $esperados = array_map(
            fn (Empleo $vacante): int => (int) $vacante->id,
            $this->vacantes
        );

        rsort($esperados);

        $this->assertSame(
            array_slice($esperados, 10, 10),
            array_column($respuesta->json('data'), 'id')
        );
    }

    public function test_http_pagina_fuera_del_rango_devuelve_200_vacio(): void
    {
        $titulo = $this->prepararGrupoPaginado(23);
        $this->autenticarCandidatoHttp();

        $this->getJson($this->urlBusquedaHttp([
            'titulo' => $titulo,
            'page' => 4,
        ]))
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.current_page', 4)
            ->assertJsonPath('meta.total', 23)
            ->assertJsonPath('meta.last_page', 3);
    }

    public function test_http_sin_coincidencias_devuelve_200_vacio(): void
    {
        $titulo = $this->prepararGrupoPaginado();
        $this->autenticarCandidatoHttp();

        $this->getJson($this->urlBusquedaHttp([
            'titulo' => $titulo,
            'salario_minimo' => 9000,
        ]))
            ->assertOk()
            ->assertJsonPath('data', [])
            ->assertJsonPath('meta.total', 0)
            ->assertJsonPath('meta.last_page', 1);
    }

    public function test_http_no_expone_conteos_ni_datos_privados(): void
    {
        $titulo = $this->prepararGrupoPaginado();
        $this->autenticarCandidatoHttp();

        // Mantener la vacante disponible durante la prueba de conteo.
        $this->vacantes[2]->update([
            'numero_vacantes' => 11,
        ]);

        $this->crearPostulaciones(
            10,
            (int) $this->vacantes[2]->id
        );

        $respuesta = $this->getJson(
            $this->urlBusquedaHttp(['titulo' => $titulo])
        );

        $respuesta
            ->assertOk()
            ->assertJsonPath('meta.total', 3)
            ->assertJsonPath(
                'data.0.postulantes_aprox',
                'Entre 10 y 24 postulantes'
            );

        foreach ($respuesta->json('data') as $tarjeta) {
            foreach ([
                'postulaciones_count',
                'postulaciones',
                'pagos',
                'revision',
                'empresa_id',
                'nivel_puesto_dueno_id',
                'porcentaje_match',
            ] as $campo) {
                $this->assertArrayNotHasKey($campo, $tarjeta);
            }

            $this->assertSame(
                ['razon_social', 'sector'],
                array_keys($tarjeta['empresa'])
            );
        }
    }

    public function test_http_vacante_pausada_no_aparece(): void
    {
        $titulo = $this->prepararGrupoPaginado();
        $this->autenticarCandidatoHttp();

        DB::table('empleos')
            ->where('id', $this->vacantes[0]->id)
            ->update(['estado_empleo' => 'pausada']);

        $respuesta = $this->getJson(
            $this->urlBusquedaHttp(['titulo' => $titulo])
        );

        $respuesta
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        $this->assertNotContains(
            (int) $this->vacantes[0]->id,
            array_column($respuesta->json('data'), 'id')
        );
    }

    #[DataProvider('seleccionDeHabilidadesBlandasHttp')]
    public function test_http_busca_habilidades_blandas_y_mixtas(
        bool $incluirTecnica
    ): void {
        $titulo = $this->prepararGrupoPaginado();
        $this->autenticarCandidatoHttp();

        DB::table('habilidades')
            ->where('id', $this->otraHabilidadId)
            ->update([
                'tipo_habilidad' => 'blanda',
                'area_estudio_id' => null,
            ]);

        $this->vacantes[1]->habilidades()->attach(
            $this->otraHabilidadId,
            ['tipo_requisito' => 'nice_to_have']
        );

        $habilidades = [$this->otraHabilidadId];

        if ($incluirTecnica) {
            $habilidades[] = $this->pythonId;
        }

        $respuesta = $this->getJson($this->urlBusquedaHttp([
            'titulo' => $titulo,
            'habilidades' => $habilidades,
        ]));

        $esperados = $incluirTecnica
            ? [
                (int) $this->vacantes[0]->id,
                (int) $this->vacantes[1]->id,
                (int) $this->vacantes[2]->id,
            ]
            : [(int) $this->vacantes[1]->id];

        $respuesta
            ->assertOk()
            ->assertJsonPath('meta.total', count($esperados));

        $obtenidos = array_column(
            $respuesta->json('data'),
            'id'
        );

        sort($esperados);
        sort($obtenidos);

        $this->assertSame($esperados, $obtenidos);
    }

    public static function seleccionDeHabilidadesBlandasHttp(): array
    {
        return [
            'solo blanda' => [false],
            'blanda y tecnica con coincidencia OR' => [true],
        ];
    }

    #[DataProvider('catalogosDesactivadosHttp')]
    public function test_http_rechaza_seleccion_desactivada(
        string $catalogo
    ): void {
        $this->autenticarCandidatoHttp();

        switch ($catalogo) {
            case 'habilidad':
                DB::table('habilidades')
                    ->where('id', $this->pythonId)
                    ->update(['activo' => false]);

                $filtros = [
                    'habilidades' => [$this->pythonId],
                ];
                $campo = 'habilidades.0';
                break;

            case 'nivel':
                DB::table('niveles_puesto')
                    ->where('id', $this->juniorId)
                    ->update(['estado' => 'inactivo']);

                $filtros = [
                    'niveles_puesto' => [$this->juniorId],
                ];
                $campo = 'niveles_puesto.0';
                break;

            case 'area':
                DB::table('areas_estudio')
                    ->where('id', $this->areaUnoId)
                    ->update(['activo' => false]);

                $filtros = [
                    'area_estudio_id' => $this->areaUnoId,
                ];
                $campo = 'area_estudio_id';
                break;

            default:
                $this->fail('Catálogo de prueba no implementado.');
        }

        $this->getJson($this->urlBusquedaHttp($filtros))
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors([$campo]);
    }

    public static function catalogosDesactivadosHttp(): array
    {
        return [
            'habilidad desactivada' => ['habilidad'],
            'nivel desactivado' => ['nivel'],
            'area desactivada' => ['area'],
        ];
    }

    public function test_http_buscar_no_modifica_perfil_ni_habilidades(): void
    {
        $titulo = $this->prepararGrupoPaginado();
        $usuario = $this->autenticarCandidatoHttp();

        $candidato = $usuario->candidato;

        $this->assertNotNull($candidato);

        $candidato->habilidades()->attach($this->sqlId);

        $perfilAntes = $candidato->getAttributes();

        $habilidadesAntes = $candidato->habilidades()
            ->orderBy('habilidades.id')
            ->pluck('habilidades.id')
            ->all();

        $this->getJson($this->urlBusquedaHttp([
            'titulo' => $titulo,
            'habilidades' => [$this->pythonId],
        ]))
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertSame(
            $perfilAntes,
            $candidato->fresh()->getAttributes()
        );

        $this->assertSame(
            $habilidadesAntes,
            $candidato->habilidades()
                ->orderBy('habilidades.id')
                ->pluck('habilidades.id')
                ->all()
        );
    }

    public function test_modelo_y_busqueda_coinciden_en_primera_publicacion(): void
    {
        $titulo = $this->prepararGrupoPaginado();

        $vacanteId = (int) $this->vacantes[0]->id;

        DB::table('pagos_vacante')
            ->where('empleo_id', $vacanteId)
            ->update(['created_at' => '2026-10-24 10:00:00']);

        DB::table('pagos_vacante')->insert([
            'empleo_id' => $vacanteId,
            'resultado' => 'exitoso',
            'monto' => '200.00',
            'moneda' => 'PEN',
            'tarjeta_ultimos4' => '1111',
            'created_at' => '2026-10-24 08:00:00',
            'updated_at' => '2026-10-24 08:00:00',
        ]);

        DB::table('pagos_vacante')->insert([
            'empleo_id' => $vacanteId,
            'resultado' => 'fallido',
            'monto' => '200.00',
            'moneda' => 'PEN',
            'tarjeta_ultimos4' => '0002',
            'created_at' => '2026-10-24 07:00:00',
            'updated_at' => '2026-10-24 07:00:00',
        ]);

        $modelo = Empleo::query()
            ->with([
                'pagos' => fn ($query) => $query->orderBy('id'),
            ])
            ->findOrFail($vacanteId);

        $this->assertSame(
            '2026-10-24 08:00:00',
            $modelo->publicadaEn()?->format('Y-m-d H:i:s')
        );

        $pagina = $this->app
            ->make(BusquedaVacantesService::class)
            ->buscar(['titulo' => $titulo]);

        $resultado = $pagina->getCollection()->firstWhere(
            'id',
            $vacanteId
        );

        $this->assertNotNull($resultado);

        $this->assertSame(
            '2026-10-24 08:00:00',
            $resultado->getAttribute('publicada_en')
        );
    }

    public function test_consultas_no_crecen_por_cada_tarjeta(): void
    {
        $titulo = $this->prepararGrupoPaginado();

        // Primera búsqueda: una sola coincidencia.
        foreach ([1, 2] as $indice) {
            $this->vacantes[$indice]->update([
                'titulo' => 'Fuera del grupo '.$this->vacantes[$indice]->id,
            ]);
        }

        $servicio = $this->app->make(BusquedaVacantesService::class);
        $conexion = DB::connection();
        $registrabaConsultas = $conexion->logging();

        $medir = function () use (
            $titulo,
            $servicio,
            $conexion
        ): array {
            $conexion->flushQueryLog();

            $pagina = $servicio->buscar(['titulo' => $titulo]);
            $consultasBusqueda = count($conexion->getQueryLog());

            $conexion->flushQueryLog();

            $datos = VacanteBusquedaResource::collection(
                $pagina->getCollection()
            )->resolve(
                Request::create(
                    '/api/v1/vacantes',
                    'GET'
                )
            );

            $consultasRecurso = count($conexion->getQueryLog());

            return [
                'tarjetas' => count($datos),
                'busqueda' => $consultasBusqueda,
                'recurso' => $consultasRecurso,
            ];
        };

        $conexion->enableQueryLog();

        try {
            $unaTarjeta = $medir();

            $this->prepararGrupoPaginado(10);

            $diezTarjetas = $medir();
        } finally {
            $conexion->flushQueryLog();

            if (! $registrabaConsultas) {
                $conexion->disableQueryLog();
            }
        }

        $this->assertSame(1, $unaTarjeta['tarjetas']);
        $this->assertSame(10, $diezTarjetas['tarjetas']);

        $this->assertGreaterThan(0, $unaTarjeta['busqueda']);

        $this->assertSame(
            $unaTarjeta['busqueda'],
            $diezTarjetas['busqueda'],
            'La cantidad de consultas no debe crecer por cada tarjeta.'
        );

        $this->assertSame(0, $unaTarjeta['recurso']);
        $this->assertSame(0, $diezTarjetas['recurso']);
    }

    #[DataProvider('configuracionesDebugHttp')]
    public function test_http_error_inesperado_no_expone_detalles(
        bool $debug
    ): void {
        config(['app.debug' => $debug]);

        $this->autenticarCandidatoHttp();

        $servicio = \Mockery::mock(BusquedaVacantesService::class);

        $servicio
            ->shouldReceive('buscar')
            ->once()
            ->andThrow(new \RuntimeException(
                'SQLSTATE prueba interna; token=secreto-de-prueba; '
                .'/var/www/html/app/Services/Vacantes/BusquedaVacantesService.php'
            ));

        $this->app->instance(
            BusquedaVacantesService::class,
            $servicio
        );

        $respuesta = $this->getJson('/api/v1/vacantes');

        $respuesta
            ->assertStatus(500)
            ->assertExactJson([
                'success' => false,
                'message' => 'No se pudo completar la solicitud. Inténtalo nuevamente.',
                'error_code' => 'SERVER_ERROR',
            ]);

        foreach ([
            'SQLSTATE',
            'secreto-de-prueba',
            '/var/www/html',
            'RuntimeException',
        ] as $detalle) {
            $this->assertStringNotContainsString(
                $detalle,
                $respuesta->getContent()
            );
        }
    }

    public static function configuracionesDebugHttp(): array
    {
        return [
            'debug desactivado' => [false],
            'debug activado' => [true],
        ];
    }
}
