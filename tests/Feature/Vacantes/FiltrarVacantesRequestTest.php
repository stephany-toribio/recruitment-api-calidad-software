<?php

namespace Tests\Feature\Vacantes;

use App\Http\Requests\Vacantes\FiltrarVacantesRequest;
use App\Services\Habilidades\HabilidadService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class FiltrarVacantesRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertTrue($this->app->environment('testing'));
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());

        Schema::create('areas_estudio', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->boolean('activo');
        });

        Schema::create('habilidades', function (Blueprint $table): void {
            $table->id();
            $table->string('nombre');
            $table->string('tipo_habilidad');
            $table->unsignedBigInteger('area_estudio_id')->nullable();
            $table->boolean('activo');
        });

        Schema::create('niveles_puesto', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('candidato_id')->nullable();
            $table->string('nombre');
            $table->string('estado');
        });

        DB::table('areas_estudio')->insert([
            ['id' => 1, 'nombre' => 'Área activa', 'activo' => true],
            ['id' => 2, 'nombre' => 'Área inactiva', 'activo' => false],
        ]);

        DB::table('niveles_puesto')->insert([
            [
                'id' => 1,
                'candidato_id' => null,
                'nombre' => 'Junior',
                'estado' => 'activo',
            ],
            [
                'id' => 2,
                'candidato_id' => null,
                'nombre' => 'Senior',
                'estado' => 'activo',
            ],
            [
                'id' => 3,
                'candidato_id' => null,
                'nombre' => 'Nivel deshabilitado',
                'estado' => 'inactivo',
            ],
            [
                'id' => 4,
                'candidato_id' => 99,
                'nombre' => 'Nivel personalizado',
                'estado' => 'activo',
            ],
        ]);

        foreach (range(1, 11) as $id) {
            DB::table('habilidades')->insert([
                'id' => $id,
                'nombre' => "Habilidad {$id}",
                'tipo_habilidad' => 'tecnica',
                'area_estudio_id' => 1,
                'activo' => true,
            ]);
        }

        DB::table('habilidades')->insert([
            [
                'id' => 12,
                'nombre' => 'Habilidad desactivada',
                'tipo_habilidad' => 'tecnica',
                'area_estudio_id' => 1,
                'activo' => false,
            ],
            [
                'id' => 13,
                'nombre' => 'Técnica con área inactiva',
                'tipo_habilidad' => 'tecnica',
                'area_estudio_id' => 2,
                'activo' => true,
            ],
            [
                'id' => 14,
                'nombre' => 'Técnica sin área',
                'tipo_habilidad' => 'tecnica',
                'area_estudio_id' => null,
                'activo' => true,
            ],
            [
                'id' => 15,
                'nombre' => 'Comunicación',
                'tipo_habilidad' => 'blanda',
                'area_estudio_id' => null,
                'activo' => true,
            ],
            [
                'id' => 16,
                'nombre' => 'Técnica con área inexistente',
                'tipo_habilidad' => 'tecnica',
                'area_estudio_id' => 999,
                'activo' => true,
            ],
        ]);
    }

    private function validar(array $parametros): array
    {
        $request = FiltrarVacantesRequest::create(
            '/api/v1/vacantes',
            'GET',
            $parametros
        );

        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $request->validateResolved();

        return $request->validated();
    }

    public function test_acepta_busqueda_sin_filtros_y_listas_vacias(): void
    {
        $this->assertSame([], $this->validar([]));

        $entrada = [
            'habilidades' => [],
            'niveles_puesto' => [],
        ];

        $this->assertSame($entrada, $this->validar($entrada));
    }

    public function test_normaliza_el_titulo(): void
    {
        foreach (['', '   ', null] as $titulo) {
            $this->assertSame([], $this->validar(['titulo' => $titulo]));
        }

        $this->assertSame(
            ['titulo' => 'Analista Python'],
            $this->validar(['titulo' => '  Analista Python  '])
        );

        $titulo = str_repeat('a', 100);

        $this->assertSame(
            ['titulo' => $titulo],
            $this->validar(['titulo' => $titulo])
        );
    }

    public function test_acepta_tres_habilidades_y_filtros_de_query_string(): void
    {
        $entrada = [
            'titulo' => 'Analista',
            'habilidades' => ['1', '2', '3'],
            'niveles_puesto' => ['1', '2'],
            'area_estudio_id' => '1',
            'solo_remoto' => '1',
            'salario_minimo' => '3000',
            'page' => '2',
        ];

        $resultado = $this->validar($entrada);

        ksort($entrada);
        ksort($resultado);

        $this->assertSame($entrada, $resultado);
    }

    public function test_acepta_los_cinco_minimos_salariales(): void
    {
        foreach ([1500, 3000, 5000, 7000, 9000] as $importe) {
            $this->assertSame(
                ['salario_minimo' => $importe],
                $this->validar(['salario_minimo' => $importe])
            );

            $this->assertSame(
                ['salario_minimo' => (string) $importe],
                $this->validar(['salario_minimo' => (string) $importe])
            );
        }
    }

    public function test_acepta_remoto_encendido_y_apagado(): void
    {
        foreach ([0, 1, '0', '1', false, true] as $valor) {
            $this->assertSame(
                ['solo_remoto' => $valor],
                $this->validar(['solo_remoto' => $valor])
            );
        }
    }

    public function test_requisito_anterior_no_forma_parte_de_datos_validados(): void
    {
        $this->assertSame(
            [],
            $this->validar(['requisito' => 'must_have'])
        );

        $this->assertSame(
            ['habilidades' => [1]],
            $this->validar([
                'habilidades' => [1],
                'requisito' => 'nice_to_have',
            ])
        );
    }

    public function test_acepta_habilidad_blanda_activa_sin_area(): void
    {
        $this->assertSame(
            ['habilidades' => [15]],
            $this->validar(['habilidades' => [15]])
        );
    }

    #[DataProvider('entradasInvalidas')]
    public function test_rechaza_entradas_invalidas(
        array $entrada,
        string $campo
    ): void {
        try {
            $this->validar($entrada);
        } catch (ValidationException $error) {
            $this->assertArrayHasKey($campo, $error->errors());

            return;
        }

        $this->fail('La entrada debía producir un error de validación.');
    }

    public static function entradasInvalidas(): array
    {
        return [
            'titulo largo' => [
                ['titulo' => str_repeat('a', 101)], 'titulo',
            ],
            'titulo como lista' => [
                ['titulo' => ['Python']], 'titulo',
            ],
            'habilidades como texto' => [
                ['habilidades' => '1,2'], 'habilidades',
            ],
            'cuatro habilidades activas' => [
                ['habilidades' => [1, 2, 3, 4]], 'habilidades',
            ],
            'habilidades repetidas' => [
                ['habilidades' => [1, '1']], 'habilidades.0',
            ],
            'habilidad inexistente' => [
                ['habilidades' => [999]], 'habilidades.0',
            ],
            'habilidad desactivada' => [
                ['habilidades' => [12]], 'habilidades.0',
            ],
            'habilidad cero' => [
                ['habilidades' => [0]], 'habilidades.0',
            ],
            'habilidad negativa' => [
                ['habilidades' => [-1]], 'habilidades.0',
            ],
            'habilidad decimal' => [
                ['habilidades' => [1.5]], 'habilidades.0',
            ],
            'habilidad como texto' => [
                ['habilidades' => ['Python']], 'habilidades.0',
            ],
            'habilidad nula' => [
                ['habilidades' => [null]], 'habilidades.0',
            ],
            'habilidad anidada' => [
                ['habilidades' => [[1]]], 'habilidades.0',
            ],
            'habilidades con indices no consecutivos' => [
                ['habilidades' => [2 => 1]], 'habilidades',
            ],
            'tecnica con area inactiva' => [
                ['habilidades' => [13]], 'habilidades.0',
            ],
            'tecnica sin area' => [
                ['habilidades' => [14]], 'habilidades.0',
            ],
            'tecnica con area inexistente' => [
                ['habilidades' => [16]], 'habilidades.0',
            ],
            'niveles como texto' => [
                ['niveles_puesto' => '1,2'], 'niveles_puesto',
            ],
            'niveles repetidos' => [
                ['niveles_puesto' => [1, '1']], 'niveles_puesto.0',
            ],
            'nivel inexistente' => [
                ['niveles_puesto' => [999]], 'niveles_puesto.0',
            ],
            'nivel deshabilitado' => [
                ['niveles_puesto' => [3]], 'niveles_puesto.0',
            ],
            'nivel personalizado' => [
                ['niveles_puesto' => [4]], 'niveles_puesto.0',
            ],
            'nivel cero' => [
                ['niveles_puesto' => [0]], 'niveles_puesto.0',
            ],
            'nivel negativo' => [
                ['niveles_puesto' => [-1]], 'niveles_puesto.0',
            ],
            'nivel decimal' => [
                ['niveles_puesto' => [1.5]], 'niveles_puesto.0',
            ],
            'nivel nulo' => [
                ['niveles_puesto' => [null]], 'niveles_puesto.0',
            ],
            'nivel anidado' => [
                ['niveles_puesto' => [[1]]], 'niveles_puesto.0',
            ],
            'niveles con indices no consecutivos' => [
                ['niveles_puesto' => [2 => 1]], 'niveles_puesto',
            ],
            'area inexistente' => [
                ['area_estudio_id' => 999], 'area_estudio_id',
            ],
            'area desactivada' => [
                ['area_estudio_id' => 2], 'area_estudio_id',
            ],
            'area cero' => [
                ['area_estudio_id' => 0], 'area_estudio_id',
            ],
            'area negativa' => [
                ['area_estudio_id' => -1], 'area_estudio_id',
            ],
            'area decimal' => [
                ['area_estudio_id' => 1.5], 'area_estudio_id',
            ],
            'area como lista' => [
                ['area_estudio_id' => [1]], 'area_estudio_id',
            ],
            'area como texto null' => [
                ['area_estudio_id' => 'null'], 'area_estudio_id',
            ],
            'remoto como texto true' => [
                ['solo_remoto' => 'true'], 'solo_remoto',
            ],
            'remoto fuera de opciones' => [
                ['solo_remoto' => 2], 'solo_remoto',
            ],
            'remoto como lista' => [
                ['solo_remoto' => [1]], 'solo_remoto',
            ],
            'salario no ofrecido en selector' => [
                ['salario_minimo' => 2000], 'salario_minimo',
            ],
            'salario cero' => [
                ['salario_minimo' => 0], 'salario_minimo',
            ],
            'salario negativo' => [
                ['salario_minimo' => -1500], 'salario_minimo',
            ],
            'salario decimal' => [
                ['salario_minimo' => 1500.5], 'salario_minimo',
            ],
            'salario como texto' => [
                ['salario_minimo' => 'tres mil'], 'salario_minimo',
            ],
            'salario como lista' => [
                ['salario_minimo' => [1500]], 'salario_minimo',
            ],
            'pagina cero' => [
                ['page' => 0], 'page',
            ],
            'pagina negativa' => [
                ['page' => -1], 'page',
            ],
            'pagina decimal' => [
                ['page' => 1.5], 'page',
            ],
            'pagina como texto' => [
                ['page' => 'dos'], 'page',
            ],
            'pagina como lista' => [
                ['page' => [1]], 'page',
            ],
        ];
    }

    public function test_catalogo_excluye_habilidades_no_seleccionables(): void
    {
        $habilidades = $this->app
            ->make(HabilidadService::class)
            ->listarSeleccionables();

        $this->assertSame(
            [...range(1, 11), 15],
            $habilidades->pluck('id')->sort()->values()->all()
        );
    }

    public function test_filtrar_catalogo_por_area_no_oculta_habilidades_blandas(): void
    {
        $habilidades = $this->app
            ->make(HabilidadService::class)
            ->listarSeleccionables([2]);

        $this->assertSame(
            [15],
            $habilidades->pluck('id')->values()->all()
        );
    }
}
