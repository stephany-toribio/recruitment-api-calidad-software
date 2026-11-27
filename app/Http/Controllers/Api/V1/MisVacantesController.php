<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\Api\V1\VacanteEmpresaResource;
use App\Services\Vacantes\RevisionVacanteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;


class MisVacantesController extends BaseApiController
{
    public function __construct(
        private RevisionVacanteService $revisionService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        return $this->successResponse(
            VacanteEmpresaResource::collection(
                $this->revisionService->vacantesDeEmpresa($request->user()->id)
            ),
            'Vacantes de la empresa'
        );
    }
}
