<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Empresas\ActualizarEmpresaRequest;
use App\Http\Requests\Empresas\RegistrarEmpresaRequest;
use App\Http\Resources\Api\V1\EmpresaResource;
use App\Services\Empresas\EmpresaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmpresaController extends BaseApiController
{
    public function __construct(
        private EmpresaService $empresaService
    ) {
    }

    public function store(RegistrarEmpresaRequest $request): JsonResponse
    {
        $empresa = $this->empresaService->registrar(
            $request->validated()
        );

        return $this->successResponse(
            new EmpresaResource($empresa),
            'Empresa registrada correctamente',
            201
        );
    }

    public function show(Request $request): JsonResponse
    {
        $empresa = $this->empresaService->obtenerPorUsuario(
            $request->user()->id
        );

        return $this->successResponse(
            new EmpresaResource($empresa),
            'Empresa obtenida correctamente'
        );
    }

    public function update(
        ActualizarEmpresaRequest $request
    ): JsonResponse {
        $empresa = $this->empresaService->actualizarPorUsuario(
            $request->user()->id,
            $request->validated()
        );

        return $this->successResponse(
            new EmpresaResource($empresa),
            'Empresa actualizada correctamente'
        );
    }
}
