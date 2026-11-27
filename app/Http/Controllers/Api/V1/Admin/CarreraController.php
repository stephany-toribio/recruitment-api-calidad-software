<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Carreras\ActualizarCarreraRequest;
use App\Http\Requests\Carreras\CrearCarreraRequest;
use App\Http\Requests\Carreras\FiltrarCarrerasAdminRequest;
use App\Http\Resources\Api\V1\CarreraAdminResource;
use App\Models\Carrera;
use App\Services\Admin\CarreraAdminService;
use Illuminate\Http\JsonResponse;

class CarreraController extends BaseApiController
{
    public function __construct(
        private CarreraAdminService $carreraService
    ) {
    }

    // GET /api/v1/admin/carreras?tipo_estudio_id=&activo=1|0
    public function index(FiltrarCarrerasAdminRequest $request): JsonResponse
    {
        return $this->successResponse(
            CarreraAdminResource::collection($this->carreraService->listar($request->validated())),
            'Carreras obtenidas correctamente.'
        );
    }

    public function store(CrearCarreraRequest $request): JsonResponse
    {
        $carrera = $this->carreraService->crear($request->validated());

        return $this->successResponse(
            new CarreraAdminResource($carrera),
            'Carrera creada correctamente.',
            201
        );
    }

    public function update(ActualizarCarreraRequest $request, Carrera $carrera): JsonResponse
    {
        $carrera = $this->carreraService->actualizar($carrera, $request->validated());

        return $this->successResponse(
            new CarreraAdminResource($carrera),
            'Carrera actualizada correctamente.'
        );
    }

    public function desactivar(Carrera $carrera): JsonResponse
    {
        return $this->successResponse(
            new CarreraAdminResource($this->carreraService->desactivar($carrera)),
            'Carrera desactivada correctamente.'
        );
    }

    public function reactivar(Carrera $carrera): JsonResponse
    {
        return $this->successResponse(
            new CarreraAdminResource($this->carreraService->reactivar($carrera)),
            'Carrera reactivada correctamente.'
        );
    }
}
