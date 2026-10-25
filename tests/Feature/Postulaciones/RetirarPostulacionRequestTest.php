<?php

namespace Tests\Feature\Postulaciones;

use App\Http\Requests\Postulaciones\RetirarPostulacionRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RetirarPostulacionRequestTest extends TestCase
{
    private const RUTA = '/api/v1/__tests/hu16/postulaciones/15/retirar';

    protected function setUp(): void
    {
        parent::setUp();

        Route::patch(
            '/api/v1/__tests/hu16/postulaciones/{postulacion}/retirar',
            function (RetirarPostulacionRequest $request) {
                return response()->json([
                    'success' => true,
                    'data' => $request->validated(),
                ]);
            }
        )->whereNumber('postulacion');
    }

    public function test_acepta_solicitud_sin_body(): void
    {
        $respuesta = $this->call(
            'PATCH',
            self::RUTA,
            [],
            [],
            [],
            ['HTTP_ACCEPT' => 'application/json']
        );

        $respuesta
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame([], $respuesta->json('data'));
    }

    public function test_acepta_objeto_json_vacio(): void
    {
        $respuesta = $this->call(
            'PATCH',
            self::RUTA,
            [],
            [],
            [],
            [
                'HTTP_ACCEPT' => 'application/json',
                'CONTENT_TYPE' => 'application/json',
            ],
            '{}'
        );

        $respuesta
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSame([], $respuesta->json('data'));
    }

    #[DataProvider('camposNoPermitidos')]
    public function test_rechaza_campos_adicionales(
        string $campo,
        mixed $valor
    ): void {
        $this->patchJson(self::RUTA, [
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
            'postulacion_id',
            'candidato_id',
            'usuario_id',
            'vacante_id',
            'empleo_id',
            'estado_postulacion_id',
            'estado',
            'retirada',
            'retirada_en',
            'fecha_postulacion',
            'porcentaje_match',
            'motivo',
            'otro_campo',
        ];

        foreach ($campos as $campo) {
            yield $campo.' con valor' => [$campo, 'valor'];

            yield $campo.' con null' => [$campo, null];

            yield $campo.' vacio' => [$campo, ''];
        }
    }

    public function test_rechaza_parametros_de_consulta(): void
    {
        $this->patchJson(
            self::RUTA.'?candidato_id=999'
        )
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['candidato_id']);
    }

    public function test_rechaza_archivos_adjuntos(): void
    {
        $archivo = UploadedFile::fake()->create(
            'documento.pdf',
            10,
            'application/pdf'
        );

        $this->withHeader('Accept', 'application/json')
            ->patch(self::RUTA, [
                'archivo' => $archivo,
            ])
            ->assertUnprocessable()
            ->assertJsonPath('error_code', 'VALIDATION_ERROR')
            ->assertJsonValidationErrors(['archivo']);
    }
}
