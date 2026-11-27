<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Habilidades\ActualizarHabilidadRequest;
use App\Http\Requests\Habilidades\CrearHabilidadRequest;
use App\Http\Requests\Habilidades\FiltrarHabilidadesRequest;
use App\Http\Resources\Api\V1\HabilidadResource;
use App\Models\Habilidad;
use App\Services\Habilidades\HabilidadService;
use Illuminate\Http\JsonResponse;

class HabilidadController extends BaseApiController
{
    public function __construct(
        private HabilidadService $habilidadService
    ) {
    }

    // GET /api/v1/habilidades?areas[]= (filtro opcional: técnicas de esas áreas + todas las blandas)
    public function index(FiltrarHabilidadesRequest $request): JsonResponse
    {
        $habilidades = $this->habilidadService->listarSeleccionables(
            $request->validated('areas', [])
        );

        return $this->successResponse(
            HabilidadResource::collection($habilidades),
            'Catálogo de habilidades obtenido correctamente.'
        );
    }

    public function adminIndex(): JsonResponse
    {
        $habilidades = $this->habilidadService->listarTodas();

        return $this->successResponse(
            HabilidadResource::collection($habilidades),
            'Habilidades obtenidas correctamente.'
        );
    }

    public function store(CrearHabilidadRequest $request): JsonResponse
    {
        $habilidad = $this->habilidadService->crear(
            $request->validated()
        );

        return $this->successResponse(
            new HabilidadResource($habilidad),
            'Habilidad creada correctamente.',
            201
        );
    }

    public function update(
        ActualizarHabilidadRequest $request,
        Habilidad $habilidad
    ): JsonResponse {
        $habilidad = $this->habilidadService->actualizar(
            $habilidad,
            $request->validated()
        );

        return $this->successResponse(
            new HabilidadResource($habilidad),
            'Habilidad actualizada correctamente.'
        );
    }

    public function desactivar(Habilidad $habilidad): JsonResponse
    {
        $habilidad = $this->habilidadService->desactivar($habilidad);

        return $this->successResponse(
            new HabilidadResource($habilidad),
            'Habilidad desactivada correctamente.'
        );
    }

    public function reactivar(Habilidad $habilidad): JsonResponse
    {
        $habilidad = $this->habilidadService->reactivar($habilidad);

        return $this->successResponse(
            new HabilidadResource($habilidad),
            'Habilidad reactivada correctamente.'
        );
    }
}
