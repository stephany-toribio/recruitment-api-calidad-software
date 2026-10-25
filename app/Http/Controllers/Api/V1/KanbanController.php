<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BusinessException;
use App\Http\Requests\Postulaciones\RechazarPostulacionRequest;
use App\Models\Empleo;
use App\Models\Empresa;
use App\Models\MotivoRechazo;
use App\Services\Postulaciones\KanbanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class KanbanController extends BaseApiController
{
    public function __construct(
        private KanbanService $kanbanService
    ) {}

    public function motivosRechazo(): JsonResponse
    {
        $motivos = MotivoRechazo::query()
            ->whereIn('motivo', MotivoRechazo::PERMITIDOS)
            ->orderBy('id')
            ->get(['id', 'motivo']);

        return $this->successResponse(
            $motivos,
            'Motivos de rechazo'
        );
    }

    public function tablero(Request $request, Empleo $vacante): JsonResponse
    {
        return $this->successResponse(
            $this->kanbanService->tablero(
                $this->empresaAutenticada($request),
                (int) $vacante->id
            ),
            'Tablero de postulantes'
        );
    }

    public function historial(Request $request, Empleo $vacante): JsonResponse
    {
        return $this->successResponse(
            $this->kanbanService->historial(
                $this->empresaAutenticada($request),
                (int) $vacante->id
            ),
            'Historial de postulantes'
        );
    }

    public function avanzar(
        Request $request,
        Empleo $vacante,
        string $postulacion
    ): JsonResponse {
        return $this->successResponse(
            $this->kanbanService->avanzar(
                $this->empresaAutenticada($request),
                (int) $vacante->id,
                (int) $postulacion
            ),
            'Postulación actualizada.'
        );
    }

    public function rechazar(
        RechazarPostulacionRequest $request,
        Empleo $vacante,
        string $postulacion
    ): JsonResponse {
        $datos = $request->validated();

        return $this->successResponse(
            $this->kanbanService->rechazar(
                $this->empresaAutenticada($request),
                (int) $vacante->id,
                (int) $postulacion,
                (int) $datos['motivo_rechazo_id']
            ),
            'Postulación actualizada.'
        );
    }

    public function contratar(
        Request $request,
        Empleo $vacante,
        string $postulacion
    ): JsonResponse {
        return $this->successResponse(
            $this->kanbanService->contratar(
                $this->empresaAutenticada($request),
                (int) $vacante->id,
                (int) $postulacion
            ),
            'Postulación actualizada.'
        );
    }

    private function empresaAutenticada(Request $request): Empresa
    {
        $empresa = $request->user()->empresa;

        if ($empresa === null) {
            throw new BusinessException(
                'No tienes permisos para realizar esta acción.',
                403,
                'FORBIDDEN'
            );
        }

        return $empresa;
    }
}