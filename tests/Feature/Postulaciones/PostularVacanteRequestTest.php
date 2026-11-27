<?php

namespace Tests\Feature\Postulaciones;

use App\Http\Requests\Postulaciones\PostularVacanteRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PostularVacanteRequestTest extends TestCase
{
    private const RUTA = '/api/v1/__tests/hu05/postular';

    protected function setUp(): void
    {
        parent::setUp();
        Route::post(
            self::RUTA,
            function (PostularVacanteRequest $request) {
                return response()->json([
                    'data' => $request->validated(),
                ]);
            }
        );
    }

    #[DataProvider('identificadoresValidos')]
    public function test_acepta_identificadores_validos(
        int|string $identificador
    ): void {
        $respuesta = $this->postJson(self::RUTA, [
            'vacante_id' => $identificador,
        ]);

        $respuesta->assertOk();

        $this->assertSame(
            ['vacante_id' => $identificador],
            $respuesta->json('data')
        );
    }

    public static function identificadoresValidos(): array
    {
        return [
            'entero positivo' => [15],
            'entero como texto' => ['15'],
            'limite entero de la plataforma' => [PHP_INT_MAX],
            'limite entero como texto' => [(string) PHP_INT_MAX],
        ];
    }

    #[DataProvider('entradasInvalidas')]
    public function test_rechaza_identificadores_invalidos(
        array $entrada
    ): void {
        $this->postJson(self::RUTA, $entrada)
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['vacante_id']);
    }

    public static function entradasInvalidas(): array
    {
        return [
            'ausente' => [[]],
            'nulo' => [['vacante_id' => null]],
            'texto vacio' => [['vacante_id' => '']],
            'solo espacios' => [['vacante_id' => '   ']],
            'cero' => [['vacante_id' => 0]],
            'cero como texto' => [['vacante_id' => '0']],
            'negativo' => [['vacante_id' => -1]],
            'negativo como texto' => [['vacante_id' => '-1']],
            'decimal' => [['vacante_id' => 1.5]],
            'decimal como texto' => [['vacante_id' => '1.5']],
            'booleano verdadero' => [['vacante_id' => true]],
            'booleano falso' => [['vacante_id' => false]],
            'texto no numerico' => [['vacante_id' => 'vacante']],
            'texto null' => [['vacante_id' => 'null']],
            'texto undefined' => [['vacante_id' => 'undefined']],
            'lista vacia' => [['vacante_id' => []]],
            'lista con identificador' => [['vacante_id' => [15]]],
            'objeto' => [['vacante_id' => ['id' => 15]]],
            'notacion cientifica como texto' => [
                ['vacante_id' => '1e3'],
            ],
            'identificador fuera de rango' => [
                ['vacante_id' => '999999999999999999999999999999'],
            ],
            'contenido sql' => [
                ['vacante_id' => '1 OR 1=1'],
            ],
        ];
    }

    #[DataProvider('camposNoPermitidos')]
    public function test_rechaza_campos_fuera_del_contrato(
        string $campo,
        mixed $valor
    ): void {
        $this->postJson(self::RUTA, [
            'vacante_id' => 15,
            $campo => $valor,
        ])
            ->assertUnprocessable()
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors([$campo]);
    }

    public static function camposNoPermitidos(): iterable
    {
        $campos = [
            'candidato_id',
            'usuario_id',
            'estado_postulacion_id',
            'estado',
            'fecha_postulacion',
            'retirada_en',
            'porcentaje_match',
            'carta_presentacion',
            'cv_adjunto',
            'expectativa_salarial',
            'salario_esperado',
            'otro_campo',
        ];

        foreach ($campos as $campo) {
            yield $campo.' con valor' => [$campo, 'valor'];

            yield $campo.' con null' => [$campo, null];
        }
    }

    public function test_rechaza_archivo_cv_adjunto(): void
    {
        $archivo = UploadedFile::fake()->create(
            'cv.pdf',
            10,
            'application/pdf'
        );

        $this->withHeader('Accept', 'application/json')
            ->post(self::RUTA, [
                'vacante_id' => '15',
                'cv_adjunto' => $archivo,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['cv_adjunto']);
    }

    public function test_rechaza_campo_adicional_en_query_string(): void
    {
        $this->postJson(
            self::RUTA.'?candidato_id=999',
            ['vacante_id' => 15]
        )
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['candidato_id']);
    }
}
