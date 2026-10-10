<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\NivelesPuesto\ActualizarNivelPuestoRequest;
use App\Http\Requests\NivelesPuesto\CrearNivelPuestoRequest;
use App\Http\Requests\NivelesPuesto\FiltrarNivelesPuestoRequest;
use App\Http\Resources\Api\V1\NivelPuestoResource;
use App\Models\NivelPuesto;
use App\Services\Admin\NivelPuestoAdminService;
use Illuminate\Http\JsonResponse;

class NivelPuestoController extends BaseApiController
{
    public function __construct(
        private NivelPuestoAdminService $nivelService
    ) {
    }

    // GET /api/v1/admin/niveles-puesto?tipo=global|personalizado&estado=activo|inactivo
    public function index(FiltrarNivelesPuestoRequest $request): JsonResponse
    {
        return $this->successResponse(
            NivelPuestoResource::collection($this->nivelService->listar($request->validated())),
            'Niveles del puesto obtenidos correctamente.'
        );
    }

    public function store(CrearNivelPuestoRequest $request): JsonResponse
    {
        $nivel = $this->nivelService->crear($request->validated());

        return $this->successResponse(
            new NivelPuestoResource($nivel),
            'Nivel del puesto creado correctamente.',
            201
        );
    }

    public function update(ActualizarNivelPuestoRequest $request, NivelPuesto $nivelPuesto): JsonResponse
    {
        $nivel = $this->nivelService->actualizar($nivelPuesto, $request->validated());

        return $this->successResponse(
            new NivelPuestoResource($nivel),
            'Nivel del puesto actualizado correctamente.'
        );
    }

    public function desactivar(NivelPuesto $nivelPuesto): JsonResponse
    {
        return $this->successResponse(
            new NivelPuestoResource($this->nivelService->desactivar($nivelPuesto)),
            'Nivel del puesto desactivado correctamente.'
        );
    }

    public function reactivar(NivelPuesto $nivelPuesto): JsonResponse
    {
        return $this->successResponse(
            new NivelPuestoResource($this->nivelService->reactivar($nivelPuesto)),
            'Nivel del puesto activado correctamente.'
        );
    }
}
