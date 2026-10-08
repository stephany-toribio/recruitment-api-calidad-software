<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
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

    public function index(): JsonResponse
    {
        return $this->successResponse(
            PuestoResource::collection($this->puestoService->listar()),
            'Puestos obtenidos correctamente.'
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
