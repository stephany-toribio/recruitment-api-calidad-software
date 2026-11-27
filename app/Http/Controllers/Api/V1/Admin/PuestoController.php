<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Puestos\ActualizarPuestoRequest;
use App\Http\Requests\Puestos\CrearPuestoRequest;
use App\Http\Requests\Puestos\FiltrarPuestosRequest;
use App\Http\Resources\Api\V1\PuestoResource;
use App\Models\Puesto;
use App\Services\Admin\PuestoAdminService;
use Illuminate\Http\JsonResponse;

class PuestoController extends BaseApiController
{
    public function __construct(
        private PuestoAdminService $puestoService
    ) {
    }

    // GET /api/v1/admin/puestos?tipo=global|personalizado&estado=activo|inactivo
    public function index(FiltrarPuestosRequest $request): JsonResponse
    {
        return $this->successResponse(
            PuestoResource::collection($this->puestoService->listar($request->validated())),
            'Puestos obtenidos correctamente.'
        );
    }

    public function store(CrearPuestoRequest $request): JsonResponse
    {
        $puesto = $this->puestoService->crear($request->validated());

        return $this->successResponse(
            new PuestoResource($puesto),
            'Puesto creado correctamente.',
            201
        );
    }

    public function update(ActualizarPuestoRequest $request, Puesto $puesto): JsonResponse
    {
        $puesto = $this->puestoService->actualizar($puesto, $request->validated());

        return $this->successResponse(
            new PuestoResource($puesto),
            'Puesto actualizado correctamente.'
        );
    }

    public function desactivar(Puesto $puesto): JsonResponse
    {
        return $this->successResponse(
            new PuestoResource($this->puestoService->desactivar($puesto)),
            'Puesto desactivado correctamente.'
        );
    }

    public function reactivar(Puesto $puesto): JsonResponse
    {
        return $this->successResponse(
            new PuestoResource($this->puestoService->reactivar($puesto)),
            'Puesto activado correctamente.'
        );
    }
}
