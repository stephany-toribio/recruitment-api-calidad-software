<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Catalogos\FiltrarPorPaisRequest;
use App\Http\Resources\Api\V1\CatalogoResource;
use App\Services\Catalogos\CatalogoService;
use Illuminate\Http\JsonResponse;

class CatalogoController extends BaseApiController
{
    public function __construct(private CatalogoService $catalogoService) {}


    // GET /api/v1/paises
    public function paises(): JsonResponse
    {
        return $this->successResponse(
            CatalogoResource::collection($this->catalogoService->paises()),
            'Catálogo de países obtenido correctamente'
        );
    }


    // GET /api/v1/tipos-estudio
    public function tiposEstudio(): JsonResponse
    {
        return $this->successResponse(
            CatalogoResource::collection($this->catalogoService->tiposEstudio()),
            'Catálogo de tipos de estudio obtenido correctamente'
        );
    }


    // GET /api/v1/areas-estudio
    public function areasEstudio(): JsonResponse
    {
        return $this->successResponse(
            CatalogoResource::collection($this->catalogoService->areasEstudio()),
            'Catálogo de áreas de estudio obtenido correctamente'
        );
    }


    // GET /api/v1/instituciones?pais_id=
    public function instituciones(FiltrarPorPaisRequest $request): JsonResponse
    {
        return $this->successResponse(
            CatalogoResource::collection($this->catalogoService->instituciones($request->validated('pais_id'))),
            'Catálogo de instituciones obtenido correctamente'
        );
    }


    // GET /api/v1/idiomas
    public function idiomas(): JsonResponse
    {
        return $this->successResponse(
            CatalogoResource::collection($this->catalogoService->idiomas()),
            'Catálogo de idiomas obtenido correctamente'
        );
    }


    // GET /api/v1/puestos
    public function puestos(): JsonResponse
    {
        return $this->successResponse(
            CatalogoResource::collection($this->catalogoService->puestos()),
            'Catálogo de puestos obtenido correctamente'
        );
    }


    // GET /api/v1/organizaciones?pais_id=
    public function organizaciones(FiltrarPorPaisRequest $request): JsonResponse
    {
        return $this->successResponse(
            CatalogoResource::collection($this->catalogoService->organizaciones($request->validated('pais_id'))),
            'Catálogo de organizaciones obtenido correctamente'
        );
    }
}
