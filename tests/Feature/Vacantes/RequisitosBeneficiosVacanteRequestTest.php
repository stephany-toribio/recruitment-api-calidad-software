<?php

namespace Tests\Feature\Vacantes;

use App\Http\Requests\Vacantes\RegistrarVacanteRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * HU-03: requisitos y beneficios opcionales de la vacante (listas de hasta 10 textos).
 */
class RequisitosBeneficiosVacanteRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->assertSame('sqlite', DB::connection()->getDriverName());

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

        DB::table('areas_estudio')->insert(['id' => 1, 'nombre' => 'Tecnología', 'activo' => true]);
        DB::table('niveles_puesto')->insert([
            'id' => 1, 'candidato_id' => null, 'nombre' => 'Junior', 'estado' => 'activo',
        ]);
        DB::table('habilidades')->insert([
            'id' => 1, 'nombre' => 'PHP', 'tipo_habilidad' => 'tecnica', 'area_estudio_id' => 1, 'activo' => true,
        ]);
        DB::table('habilidades')->insert(array_map(fn ($id) => [
            'id' => $id, 'nombre' => "Blanda {$id}", 'tipo_habilidad' => 'blanda', 'area_estudio_id' => null, 'activo' => true,
        ], range(2, 11)));
    }

    /** La vacante exige entre 5 y 10 habilidades, sumando técnicas y blandas */
    private function habilidades(int $cantidad): array
    {
        return array_map(fn ($id) => [
            'habilidad_id' => $id,
            'requisito' => $id === 1 ? 'must_have' : 'nice_to_have',
        ], range(1, $cantidad));
    }

    private function vacanteValida(array $extra = []): array
    {
        $hoy = now('America/Lima');

        return [
            'titulo' => 'Desarrollador Backend Laravel',
            'descripcion' => str_repeat('Descripción de la vacante. ', 3),
            'nivel_puesto_id' => 1,
            'area_estudio_id' => 1,
            'tipo_empleo' => 'remoto',
            'fecha_inicio_vigencia' => $hoy->toDateString(),
            'fecha_fin_vigencia' => $hoy->copy()->addDays(30)->toDateString(),
            'max_contrataciones' => 1,
            'habilidades' => $this->habilidades(5),
            ...$extra,
        ];
    }

    private function validar(array $datos): array
    {
        $request = RegistrarVacanteRequest::create('/api/v1/empresas/me/vacantes', 'POST', $datos);
        $request->setContainer($this->app);
        $request->setRedirector($this->app->make('redirect'));
        $request->validateResolved();

        return $request->validated();
    }

    private function erroresDe(array $datos): array
    {
        try {
            $this->validar($datos);
        } catch (ValidationException $e) {
            return $e->errors();
        }

        $this->fail('Se esperaba un error de validación.');
    }

    public function test_acepta_entre_cinco_y_diez_habilidades(): void
    {
        $this->assertCount(5, $this->validar($this->vacanteValida())['habilidades']);
        $this->assertCount(10, $this->validar($this->vacanteValida(['habilidades' => $this->habilidades(10)]))['habilidades']);
    }

    public function test_rechaza_menos_de_cinco_habilidades(): void
    {
        $errores = $this->erroresDe($this->vacanteValida(['habilidades' => $this->habilidades(4)]));

        $this->assertSame(['Seleccione al menos 5 habilidades, sumando técnicas y blandas.'], $errores['habilidades']);
    }

    public function test_rechaza_mas_de_diez_habilidades(): void
    {
        $errores = $this->erroresDe($this->vacanteValida(['habilidades' => $this->habilidades(11)]));

        $this->assertSame(['No puede asociar más de 10 habilidades a la vacante, sumando técnicas y blandas.'], $errores['habilidades']);
    }

    public function test_son_opcionales(): void
    {
        $validados = $this->validar($this->vacanteValida());

        $this->assertArrayNotHasKey('requisitos', $validados);
        $this->assertArrayNotHasKey('beneficios', $validados);
    }

    public function test_recorta_espacios_y_descarta_filas_vacias(): void
    {
        $validados = $this->validar($this->vacanteValida([
            'requisitos' => ['  Inglés intermedio  ', '', '   ', 'Disponibilidad inmediata'],
            'beneficios' => ['EPS', ''],
        ]));

        $this->assertSame(['Inglés intermedio', 'Disponibilidad inmediata'], $validados['requisitos']);
        $this->assertSame(['EPS'], $validados['beneficios']);
    }

    public function test_rechaza_mas_de_diez_elementos(): void
    {
        $once = array_map(fn ($i) => "Requisito {$i}", range(1, 11));

        $errores = $this->erroresDe($this->vacanteValida(['requisitos' => $once, 'beneficios' => $once]));

        $this->assertSame(['No puede registrar más de 10 requisitos.'], $errores['requisitos']);
        $this->assertSame(['No puede registrar más de 10 beneficios.'], $errores['beneficios']);
    }

    public function test_rechaza_textos_de_mas_de_200_caracteres(): void
    {
        $errores = $this->erroresDe($this->vacanteValida(['requisitos' => [str_repeat('a', 201)]]));

        $this->assertSame(['Cada requisito no debe superar los 200 caracteres.'], $errores['requisitos.0']);
    }

    public function test_rechaza_repetidos_sin_distinguir_mayusculas(): void
    {
        $errores = $this->erroresDe($this->vacanteValida(['beneficios' => ['Seguro EPS', 'seguro eps']]));

        $this->assertContains('No puede repetir beneficios.', $errores['beneficios.0'] ?? $errores['beneficios.1']);
    }

    public function test_rechaza_valores_que_no_son_lista_de_textos(): void
    {
        $errores = $this->erroresDe($this->vacanteValida([
            'requisitos' => 'Inglés intermedio',
            'beneficios' => [123],
        ]));

        $this->assertSame(['Los requisitos deben enviarse como una lista.'], $errores['requisitos']);
        $this->assertSame(['Cada beneficio debe ser un texto.'], $errores['beneficios.0']);
    }
}
