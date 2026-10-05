<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\AreasEstudio\ActualizarAreaEstudioRequest;
use App\Http\Requests\AreasEstudio\CrearAreaEstudioRequest;
use App\Http\Resources\Api\V1\AreaEstudioResource;
use App\Models\AreaEstudio;
use App\Services\AreasEstudio\AreaEstudioService;
use Illuminate\Http\JsonResponse;

class AreaEstudioController extends BaseApiController
{
    public function __construct(
        private AreaEstudioService $areaEstudioService
    ) {
    }

    public function index(): JsonResponse
    {
        $areas = $this->areaEstudioService->listarActivas();

        return $this->successResponse(
            AreaEstudioResource::collection($areas),
            'Catálogo de áreas de estudio obtenido correctamente.'
        );
    }

    public function adminIndex(): JsonResponse
    {
        $areas = $this->areaEstudioService->listarTodas();

        return $this->successResponse(
            AreaEstudioResource::collection($areas),
            'Áreas de estudio obtenidas correctamente.'
        );
    }

    public function store(CrearAreaEstudioRequest $request): JsonResponse
    {
        $area = $this->areaEstudioService->crear(
            $request->validated()
        );

        return $this->successResponse(
            new AreaEstudioResource($area),
            'Área de estudio creada correctamente.',
            201
        );
    }

    public function update(
        ActualizarAreaEstudioRequest $request,
        AreaEstudio $areaEstudio
    ): JsonResponse {
        $area = $this->areaEstudioService->actualizar(
            $areaEstudio,
            $request->validated()
        );

        return $this->successResponse(
            new AreaEstudioResource($area),
            'Área de estudio actualizada correctamente.'
        );
    }

    public function desactivar(AreaEstudio $areaEstudio): JsonResponse
    {
        $area = $this->areaEstudioService->desactivar($areaEstudio);

        return $this->successResponse(
            new AreaEstudioResource($area),
            'Área de estudio desactivada correctamente.'
        );
    }

    public function reactivar(AreaEstudio $areaEstudio): JsonResponse
    {
        $area = $this->areaEstudioService->reactivar($areaEstudio);

        return $this->successResponse(
            new AreaEstudioResource($area),
            'Área de estudio reactivada correctamente.'
        );
    }
}
