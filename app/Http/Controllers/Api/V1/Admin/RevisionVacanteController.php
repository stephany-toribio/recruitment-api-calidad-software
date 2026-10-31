<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\V1\BaseApiController;
use App\Http\Requests\Moderacion\AprobarMasivoRequest;
use App\Http\Requests\Moderacion\NoAprobarVacanteRequest;
use App\Http\Resources\Api\V1\VacanteRevisionResource;
use App\Models\Empleo;
use App\Services\Vacantes\RevisionVacanteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// HU-22: revisión administrativa de vacantes (MOD-01 a MOD-05)
class RevisionVacanteController extends BaseApiController
{
    public function __construct(
        private RevisionVacanteService $revisionService
    ) {
    }

        public function index(Request $request): JsonResponse
    {
        $filtros = $request->validate([
            'revision' => [
                'sometimes',
                'required',
                'string',
                'in:pendiente,aprobada,no_aprobada,todas',
            ],
            'page' => [
                'sometimes',
                'required',
                'integer',
                'min:1',
            ],
        ]);

        $pagina = $this->revisionService->listar(
            $filtros['revision'] ?? 'pendiente',
            (int) ($filtros['page'] ?? 1)
        );

        return response()->json([
            'success' => true,
            'message' => 'Listado administrativo de vacantes',
            'data' => VacanteRevisionResource::collection(
                $pagina->getCollection()
            )->resolve($request),
            'meta' => [
                'current_page' => $pagina->currentPage(),
                'per_page' => $pagina->perPage(),
                'total' => $pagina->total(),
                'last_page' => $pagina->lastPage(),
            ],
        ]);
    }

    public function pendientes(): JsonResponse
    {
        return $this->successResponse(
            VacanteRevisionResource::collection($this->revisionService->pendientes()),
            'Vacantes pendientes de revisión'
        );
    }

    public function show(Empleo $vacante): JsonResponse
    {
        return $this->successResponse(
            new VacanteRevisionResource($this->revisionService->detalle($vacante)),
            'Detalle de la vacante'
        );
    }

    public function aprobar(Request $request, Empleo $vacante): JsonResponse
    {
        $vacante = $this->revisionService->aprobar($vacante, $request->user()->id);

        return $this->successResponse(
            new VacanteRevisionResource($vacante),
            'Vacante aprobada. La empresa ya puede realizar el pago.'
        );
    }

    public function aprobarMasivo(AprobarMasivoRequest $request): JsonResponse
    {
        $resultado = $this->revisionService->aprobarMasivo(
            $request->validated('vacantes'),
            $request->user()->id
        );

        $cantidad = count($resultado['aprobadas']);

        return $this->successResponse(
            $resultado,
            $cantidad === 1 ? 'Se aprobó 1 vacante.' : "Se aprobaron {$cantidad} vacantes."
        );
    }

    public function noAprobar(NoAprobarVacanteRequest $request, Empleo $vacante): JsonResponse
    {
        $vacante = $this->revisionService->noAprobar(
            $vacante,
            $request->user()->id,
            $request->validated('motivo')
        );

        return $this->successResponse(
            new VacanteRevisionResource($vacante),
            'Vacante marcada como no aprobada.'
        );
    }
}
