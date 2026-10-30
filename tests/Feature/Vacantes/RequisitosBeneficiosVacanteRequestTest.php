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
            'habilidades' => [['habilidad_id' => 1, 'requisito' => 'must_have']],
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
