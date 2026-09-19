<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Habilidades\ActualizarHabilidadRequest;
use App\Http\Requests\Habilidades\CrearHabilidadRequest;
use App\Http\Resources\Api\V1\HabilidadResource;
use App\Services\Habilidades\HabilidadService;
use Illuminate\Http\JsonResponse;

class HabilidadController extends BaseApiController
{
    public function __construct(
        private HabilidadService $habilidadService
    ) {
    }

    public function index(): JsonResponse
    {
        $habilidades = $this->habilidadService->listarActivas();

        return $this->successResponse(
            HabilidadResource::collection($habilidades),
            'Catálogo de habilidades obtenido correctamente'
        );
    }

    public function store(CrearHabilidadRequest $request): JsonResponse
    {
        $habilidad = $this->habilidadService->crear(
            $request->validated()
        );

        return $this->successResponse(
            new HabilidadResource($habilidad),
            'Habilidad creada correctamente',
            201
        );
    }

    public function update(
        ActualizarHabilidadRequest $request,
        int $id
    ): JsonResponse {
        $habilidad = $this->habilidadService->actualizar(
            $id,
            $request->validated()
        );

        return $this->successResponse(
            new HabilidadResource($habilidad),
            'Habilidad actualizada correctamente'
        );
    }
}
