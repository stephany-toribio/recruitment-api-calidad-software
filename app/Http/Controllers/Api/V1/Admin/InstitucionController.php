<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Instituciones\ActualizarInstitucionRequest;
use App\Http\Requests\Instituciones\CrearInstitucionRequest;
use App\Http\Resources\Api\V1\InstitucionResource;
use App\Models\Institucion;
use App\Services\Admin\InstitucionAdminService;
use Illuminate\Http\JsonResponse;

class InstitucionController extends BaseApiController
{
    public function __construct(
        private InstitucionAdminService $institucionService
    ) {
    }

    public function index(): JsonResponse
    {
        return $this->successResponse(
            InstitucionResource::collection($this->institucionService->listar()),
            'Instituciones obtenidas correctamente.'
        );
    }

    public function store(CrearInstitucionRequest $request): JsonResponse
    {
        $institucion = $this->institucionService->crear($request->validated());

        return $this->successResponse(
            new InstitucionResource($institucion),
            'Institución creada correctamente.',
            201
        );
    }

    public function update(
        ActualizarInstitucionRequest $request,
        Institucion $institucion
    ): JsonResponse {
        $institucion = $this->institucionService->actualizar(
            $institucion,
            $request->validated()
        );

        return $this->successResponse(
            new InstitucionResource($institucion),
            'Institución actualizada correctamente.'
        );
    }

    public function desactivar(Institucion $institucion): JsonResponse
    {
        return $this->successResponse(
            new InstitucionResource($this->institucionService->desactivar($institucion)),
            'Institución desactivada correctamente.'
        );
    }

    public function reactivar(Institucion $institucion): JsonResponse
    {
        return $this->successResponse(
            new InstitucionResource($this->institucionService->reactivar($institucion)),
            'Institución reactivada correctamente.'
        );
    }
}
