<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Idiomas\ActualizarIdiomaRequest;
use App\Http\Requests\Idiomas\CrearIdiomaRequest;
use App\Http\Requests\Idiomas\FiltrarIdiomasRequest;
use App\Http\Resources\Api\V1\IdiomaAdminResource;
use App\Models\Idioma;
use App\Services\Admin\IdiomaAdminService;
use Illuminate\Http\JsonResponse;

class IdiomaController extends BaseApiController
{
    public function __construct(
        private IdiomaAdminService $idiomaService
    ) {
    }

    // GET /api/v1/admin/idiomas?activo=1|0
    public function index(FiltrarIdiomasRequest $request): JsonResponse
    {
        return $this->successResponse(
            IdiomaAdminResource::collection($this->idiomaService->listar($request->validated())),
            'Idiomas obtenidos correctamente.'
        );
    }

    public function store(CrearIdiomaRequest $request): JsonResponse
    {
        $idioma = $this->idiomaService->crear($request->validated());

        return $this->successResponse(
            new IdiomaAdminResource($idioma),
            'Idioma creado correctamente.',
            201
        );
    }

    public function update(ActualizarIdiomaRequest $request, Idioma $idioma): JsonResponse
    {
        $idioma = $this->idiomaService->actualizar($idioma, $request->validated());

        return $this->successResponse(
            new IdiomaAdminResource($idioma),
            'Idioma actualizado correctamente.'
        );
    }

    public function desactivar(Idioma $idioma): JsonResponse
    {
        return $this->successResponse(
            new IdiomaAdminResource($this->idiomaService->desactivar($idioma)),
            'Idioma desactivado correctamente.'
        );
    }

    public function reactivar(Idioma $idioma): JsonResponse
    {
        return $this->successResponse(
            new IdiomaAdminResource($this->idiomaService->reactivar($idioma)),
            'Idioma reactivado correctamente.'
        );
    }
}
