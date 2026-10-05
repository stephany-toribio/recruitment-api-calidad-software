<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Carreras\FiltrarCarrerasRequest;
use App\Http\Resources\Api\V1\CarreraResource;
use App\Services\Carreras\CarreraService;
use Illuminate\Http\JsonResponse;

class CarreraController extends BaseApiController
{
    public function __construct(private CarreraService $carreraService) {}


    // GET /api/v1/carreras?tipo_estudio_id=&area_estudio_id=&pais_id=
    public function index(FiltrarCarrerasRequest $request): JsonResponse
    {
        return $this->successResponse(
            CarreraResource::collection($this->carreraService->listarSeleccionables($request->validated())),
            'Catálogo de carreras obtenido correctamente'
        );
    }
}
