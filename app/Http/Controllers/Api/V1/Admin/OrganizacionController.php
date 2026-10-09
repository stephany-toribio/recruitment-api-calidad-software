<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Organizaciones\ActualizarOrganizacionRequest;
use App\Http\Requests\Organizaciones\CrearOrganizacionRequest;
use App\Http\Requests\Organizaciones\FiltrarOrganizacionesRequest;
use App\Http\Resources\Api\V1\OrganizacionResource;
use App\Models\Organizacion;
use App\Services\Admin\OrganizacionAdminService;
use Illuminate\Http\JsonResponse;

class OrganizacionController extends BaseApiController
{
    public function __construct(
        private OrganizacionAdminService $organizacionService
    ) {
    }

    // GET /api/v1/admin/organizaciones?tipo=global|personalizado&estado=activo|inactivo&pais_id=
    public function index(FiltrarOrganizacionesRequest $request): JsonResponse
    {
        return $this->successResponse(
            OrganizacionResource::collection($this->organizacionService->listar($request->validated())),
            'Organizaciones obtenidas correctamente.'
        );
    }

    public function store(CrearOrganizacionRequest $request): JsonResponse
    {
        $organizacion = $this->organizacionService->crear($request->validated());

        return $this->successResponse(
            new OrganizacionResource($organizacion),
            'Organización creada correctamente.',
            201
        );
    }

    public function update(ActualizarOrganizacionRequest $request, Organizacion $organizacion): JsonResponse
    {
        $organizacion = $this->organizacionService->actualizar($organizacion, $request->validated());

        return $this->successResponse(
            new OrganizacionResource($organizacion),
            'Organización actualizada correctamente.'
        );
    }

    public function desactivar(Organizacion $organizacion): JsonResponse
    {
        return $this->successResponse(
            new OrganizacionResource($this->organizacionService->desactivar($organizacion)),
            'Organización desactivada correctamente.'
        );
    }

    public function reactivar(Organizacion $organizacion): JsonResponse
    {
        return $this->successResponse(
            new OrganizacionResource($this->organizacionService->reactivar($organizacion)),
            'Organización activada correctamente.'
        );
    }
}
