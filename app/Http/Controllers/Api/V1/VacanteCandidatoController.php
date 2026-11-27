<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BusinessException;
use App\Http\Requests\Vacantes\FiltrarVacantesRequest;
use App\Http\Resources\Api\V1\VacanteBusquedaResource;
use App\Http\Resources\Api\V1\VacanteDetalleResource;
use App\Models\Candidato;
use App\Services\Vacantes\BusquedaVacantesService;
use App\Services\Vacantes\DetalleVacanteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VacanteCandidatoController extends BaseApiController
{
    public function __construct(
        private BusquedaVacantesService $busquedaVacantes,
        private DetalleVacanteService $detalleVacante
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

    public function show(
        Request $request,
        string $vacante
    ): JsonResponse {
        $detalle = $this->detalleVacante->disponible(
            (int) $vacante,
            $this->candidatoAutenticado($request)
        );

        return $this->successResponse(
            (new VacanteDetalleResource($detalle))->resolve($request),
            'Detalle de vacante obtenido correctamente.'
        );
    }

    public function historico(
        Request $request,
        string $postulacion
    ): JsonResponse {
        $detalle = $this->detalleVacante->historico(
            (int) $postulacion,
            $this->candidatoAutenticado($request)
        );

        return $this->successResponse(
            (new VacanteDetalleResource($detalle))->resolve($request),
            'Detalle histórico de vacante obtenido correctamente.'
        );
    }

    private function candidatoAutenticado(Request $request): Candidato
    {
        $candidato = $request->user()?->candidato;

        if ($candidato === null) {
            throw new BusinessException(
                'No tienes permisos para realizar esta acción.',
                403,
                'FORBIDDEN'
            );
        }

        return $candidato;
    }
}
