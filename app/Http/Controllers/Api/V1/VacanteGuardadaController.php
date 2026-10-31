<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BusinessException;
use App\Http\Resources\Api\V1\VacanteBusquedaResource;
use App\Models\Candidato;
use App\Services\Candidatos\VacanteGuardadaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VacanteGuardadaController extends BaseApiController
{
    public function __construct(
        private VacanteGuardadaService $vacantesGuardadas
    ) {}

    public function index(Request $request): JsonResponse
    {
        $vacantes = $this->vacantesGuardadas->listar(
            $this->candidatoAutenticado($request)
        );

        return $this->successResponse(
            VacanteBusquedaResource::collection($vacantes)->resolve($request),
            'Vacantes guardadas'
        );
    }

    public function guardar(Request $request, string $vacante): JsonResponse
    {
        $creada = $this->vacantesGuardadas->guardar(
            $this->candidatoAutenticado($request),
            (int) $vacante
        );

        return $this->successResponse(
            ['vacante_id' => (int) $vacante, 'guardada' => true],
            $creada ? 'Vacante guardada.' : 'La vacante ya estaba guardada.',
            $creada ? 201 : 200
        );
    }

    public function quitar(Request $request, string $vacante): JsonResponse
    {
        $this->vacantesGuardadas->quitar(
            $this->candidatoAutenticado($request),
            (int) $vacante
        );

        return $this->successResponse(
            ['vacante_id' => (int) $vacante, 'guardada' => false],
            'Vacante quitada de guardadas.'
        );
    }

    private function candidatoAutenticado(Request $request): Candidato
    {
        $candidato = $request->user()?->candidato;

        if ($candidato === null) {
            throw new BusinessException(
                'No tienes permisos para realizar esta acción.',
                403,
                'FORBIDDEN'
            );
        }

        return $candidato;
    }
}
