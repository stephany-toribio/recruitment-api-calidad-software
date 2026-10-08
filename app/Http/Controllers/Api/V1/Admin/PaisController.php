<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Paises\ActualizarPaisRequest;
use App\Http\Requests\Paises\CrearPaisRequest;
use App\Http\Resources\Api\V1\PaisResource;
use App\Models\Pais;
use App\Services\Admin\PaisAdminService;
use Illuminate\Http\JsonResponse;

class PaisController extends BaseApiController
{
    public function __construct(
        private PaisAdminService $paisService
    ) {
    }

    public function index(): JsonResponse
    {
        return $this->successResponse(
            PaisResource::collection($this->paisService->listar()),
            'Países obtenidos correctamente.'
        );
    }

    public function store(CrearPaisRequest $request): JsonResponse
    {
        $pais = $this->paisService->crear($request->validated());

        return $this->successResponse(
            new PaisResource($pais),
            'País creado correctamente.',
            201
        );
    }

    public function update(ActualizarPaisRequest $request, Pais $pais): JsonResponse
    {
        $pais = $this->paisService->actualizar($pais, $request->validated());

        return $this->successResponse(
            new PaisResource($pais),
            'País actualizado correctamente.'
        );
    }

    public function desactivar(Pais $pais): JsonResponse
    {
        return $this->successResponse(
            new PaisResource($this->paisService->desactivar($pais)),
            'País desactivado correctamente.'
        );
    }

    public function reactivar(Pais $pais): JsonResponse
    {
        return $this->successResponse(
            new PaisResource($this->paisService->reactivar($pais)),
            'País reactivado correctamente.'
        );
    }
}
