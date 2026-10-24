<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Pagos\PagarVacanteRequest;
use App\Http\Resources\Api\V1\PagoVacanteResource;
use App\Http\Resources\Api\V1\VacanteEmpresaResource;
use App\Models\Empleo;
use App\Services\Vacantes\PagoVacanteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class PagoVacanteController extends BaseApiController
{
    public function __construct(
        private PagoVacanteService $pagoService
    ) {
    }

    public function show(Request $request, Empleo $vacante): JsonResponse
    {
        $resumen = $this->pagoService->resumen($vacante, $request->user()->id);

        return $this->successResponse(
            array_merge($resumen, [
                'intentos' => PagoVacanteResource::collection($resumen['intentos']),
            ]),
            'Resumen de pago de la vacante'
        );
    }

    // Un pago rechazado también responde 201: el intento quedó registrado
    public function store(PagarVacanteRequest $request, Empleo $vacante): JsonResponse
    {
        $resultado = $this->pagoService->pagar($vacante, $request->user()->id, $request->validated());

        return $this->successResponse(
            $this->respuestaIntento($resultado),
            $resultado['pago']->fueExitoso()
                ? 'Pago simulado aprobado. La vacante ya está publicada.'
                : 'El pago simulado fue rechazado. Puedes intentarlo nuevamente.',
            201
        );
    }

    public function cancelar(Request $request, Empleo $vacante): JsonResponse
    {
        $resultado = $this->pagoService->cancelar($vacante, $request->user()->id);

        return $this->successResponse(
            $this->respuestaIntento($resultado),
            'Pago cancelado. Puedes intentarlo cuando quieras.',
            201
        );
    }

    private function respuestaIntento(array $resultado): array
    {
        return [
            'pago'    => new PagoVacanteResource($resultado['pago']),
            'vacante' => new VacanteEmpresaResource($resultado['vacante']),
        ];
    }
}
