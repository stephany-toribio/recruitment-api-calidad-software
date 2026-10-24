<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BusinessException;
use App\Http\Requests\Vacantes\RegistrarVacanteRequest;
use App\Http\Resources\Api\V1\VacanteEmpresaResource;
use App\Models\Empleo;
use App\Models\Empresa;
use App\Models\Usuario;
use App\Services\Vacantes\VacanteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VacanteEmpresaController extends BaseApiController
{
    public function __construct(
        private VacanteService $vacanteService
    ) {
    }

    public function nivelesExperiencia(): JsonResponse
    {
        $niveles = [];

        foreach (Empleo::NIVELES as $codigo => $nombre) {
            $niveles[] = [
                'codigo' => $codigo,
                'nombre' => $nombre,
            ];
        }

        return $this->successResponse(
            $niveles,
            'Niveles de experiencia obtenidos correctamente'
        );
    }

    public function store(RegistrarVacanteRequest $request): JsonResponse
    {
        $empresa = $this->empresaAutenticada($request);

        $vacante = $this->vacanteService->registrar(
            $empresa,
            $request->validated()
        );

        return $this->successResponse(
            new VacanteEmpresaResource($vacante),
            'Vacante registrada. Quedó pendiente de revisión.',
            201
        );
    }

    public function show(Request $request, Empleo $vacante): JsonResponse
    {
        $empresa = $this->empresaAutenticada($request);

        $vacante = $this->vacanteService->detalle(
            $empresa,
            $vacante
        );

        return $this->successResponse(
            new VacanteEmpresaResource($vacante),
            'Vacante obtenida correctamente'
        );
    }

    private function empresaAutenticada(Request $request): Empresa
    {
        /** @var Usuario $usuario */
        $usuario = $request->user();

        $empresa = $usuario->empresa;

        if ($empresa === null) {
            throw new BusinessException(
                'No tienes permisos para realizar esta acción.',
                403,
                'FORBIDDEN'
            );
        }

        return $empresa;
    }
}