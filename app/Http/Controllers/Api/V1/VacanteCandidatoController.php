<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Vacantes\FiltrarVacantesRequest;
use App\Http\Resources\Api\V1\VacanteBusquedaResource;
use App\Services\Vacantes\BusquedaVacantesService;
use Illuminate\Http\JsonResponse;

class VacanteCandidatoController extends BaseApiController
{
    public function __construct(
        private BusquedaVacantesService $busquedaVacantes
    ) {}

    public function index(FiltrarVacantesRequest $request): JsonResponse
    {
        $pagina = $this->busquedaVacantes->buscar(
            $request->validated()
        );

        $vacantes = VacanteBusquedaResource::collection(
            $pagina->getCollection()
        )->resolve($request);

        return response()->json([
            'success' => true,
            'message' => 'Vacantes encontradas',
            'data' => $vacantes,
            'meta' => [
                'current_page' => $pagina->currentPage(),
                'per_page' => $pagina->perPage(),
                'total' => $pagina->total(),
                'last_page' => $pagina->lastPage(),
            ],
        ]);
    }
}
