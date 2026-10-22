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

        $this->assertSame(
            'sqlite',
            DB::connection()->getDriverName()
        );

        $this->assertSame(
            ':memory:',
            DB::connection()->getDatabaseName()
        );

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

        DB::table('areas_estudio')->insert([
            ['id' => 1, 'nombre' => 'Área activa', 'activo' => true],
            ['id' => 2, 'nombre' => 'Área inactiva', 'activo' => false],
        ]);

        foreach (range(1, 11) as $id) {
            DB::table('habilidades')->insert([
                'id' => $id,
                'nombre' => "Habilidad $id",
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

    public function test_acepta_busqueda_sin_filtros(): void
    {
        $this->assertSame([], $this->validar([]));

        $this->assertSame(
            ['habilidades' => []],
            $this->validar(['habilidades' => []])
        );
    }

    public function test_normaliza_el_titulo(): void
    {
        $this->assertSame(
            [],
            $this->validar(['titulo' => '   '])
        );

        $this->assertSame(
            [],
            $this->validar(['titulo' => null])
        );

        $this->assertSame(
            ['titulo' => 'Analista Python'],
            $this->validar(['titulo' => '  Analista Python  '])
        );
    }

    public function test_acepta_diez_habilidades_y_enteros_de_query_string(): void
    {
        $ids = array_map('strval', range(1, 10));

        $datos = $this->validar([
            'habilidades' => $ids,
            'requisito' => 'must_have',
            'page' => '2',
        ]);

        $this->assertSame($ids, $datos['habilidades']);
        $this->assertSame('must_have', $datos['requisito']);
        $this->assertSame('2', $datos['page']);
    }

    public function test_ambas_se_representa_sin_requisito(): void
    {
        $datos = $this->validar([
            'titulo' => str_repeat('a', 100),
            'habilidades' => [1, 2],
        ]);

        $this->assertArrayNotHasKey('requisito', $datos);
        $this->assertSame([1, 2], $datos['habilidades']);
    }

    public function test_acepta_habilidad_deseable(): void
    {
        $datos = $this->validar([
            'habilidades' => [1],
            'requisito' => 'nice_to_have',
        ]);

        $this->assertSame('nice_to_have', $datos['requisito']);
    }

    #[DataProvider('entradasInvalidas')]
    public function test_rechaza_entradas_invalidas(
        array $entrada,
        string $campo
    ): void {
        try {
            $this->validar($entrada);
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                $campo,
                $exception->errors()
            );

            return;
        }

        $this->fail('La entrada debía producir un error de validación.');
    }

    public static function entradasInvalidas(): array
    {
        return [
            'titulo largo' => [
                ['titulo' => str_repeat('a', 101)],
                'titulo',
            ],
            'titulo como lista' => [
                ['titulo' => ['Python']],
                'titulo',
            ],
            'habilidades como texto' => [
                ['habilidades' => '1,2'],
                'habilidades',
            ],
            'once habilidades activas' => [
                ['habilidades' => range(1, 11)],
                'habilidades',
            ],
            'habilidades repetidas' => [
                ['habilidades' => [1, '1']],
                'habilidades.0',
            ],
            'habilidad inexistente' => [
                ['habilidades' => [999]],
                'habilidades.0',
            ],
            'habilidad desactivada' => [
                ['habilidades' => [12]],
                'habilidades.0',
            ],
            'identificador cero' => [
                ['habilidades' => [0]],
                'habilidades.0',
            ],
            'identificador negativo' => [
                ['habilidades' => [-1]],
                'habilidades.0',
            ],
            'identificador decimal' => [
                ['habilidades' => [1.5]],
                'habilidades.0',
            ],
            'identificador como texto' => [
                ['habilidades' => ['Python']],
                'habilidades.0',
            ],
            'lista anidada' => [
                ['habilidades' => [[1]]],
                'habilidades.0',
            ],
            'indices no consecutivos' => [
                ['habilidades' => [2 => 1]],
                'habilidades',
            ],
            'obligatoriedad sin habilidades' => [
                ['requisito' => 'must_have'],
                'habilidades',
            ],
            'obligatoriedad con lista vacia' => [
                ['habilidades' => [], 'requisito' => 'nice_to_have'],
                'habilidades',
            ],
            'valor ambas literal' => [
                ['habilidades' => [1], 'requisito' => 'ambas'],
                'requisito',
            ],
            'obligatoriedad como lista' => [
                ['habilidades' => [1], 'requisito' => ['must_have']],
                'requisito',
            ],
            'pagina cero' => [
                ['page' => 0],
                'page',
            ],
            'pagina negativa' => [
                ['page' => -1],
                'page',
            ],
            'pagina decimal' => [
                ['page' => 1.5],
                'page',
            ],
            'pagina como texto' => [
                ['page' => 'dos'],
                'page',
            ],
            'pagina como lista' => [
                ['page' => [1]],
                'page',
            ],
            'tecnica con area inactiva' => [
                ['habilidades' => [13]],
                'habilidades.0',
            ],
            'tecnica sin area' => [
                ['habilidades' => [14]],
                'habilidades.0',
            ],
            'tecnica con area inexistente' => [
                ['habilidades' => [16]],
                'habilidades.0',
            ],
        ];
    }

    public function test_acepta_habilidad_blanda_activa_sin_area(): void
    {
        $datos = $this->validar([
            'habilidades' => [15],
            'requisito' => 'nice_to_have',
        ]);

        $this->assertSame([15], $datos['habilidades']);
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

    public function test_filtrar_por_area_no_oculta_las_habilidades_blandas(): void
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
