<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BusinessException;
use App\Http\Requests\Vacantes\ActualizarVacanteRequest;
use App\Http\Requests\Vacantes\RegistrarVacanteRequest;
use App\Http\Resources\Api\V1\VacanteEmpresaResource;
use App\Models\Empleo;
use App\Models\Empresa;
use App\Models\NivelPuesto;
use App\Models\Usuario;
use App\Services\Vacantes\GestionVacanteService;
use App\Services\Vacantes\VacanteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VacanteEmpresaController extends BaseApiController
{
    public function __construct(
        private VacanteService $vacanteService,
        private GestionVacanteService $gestionService
    ) {}

    public function nivelesExperiencia(): JsonResponse
    {
        $niveles = NivelPuesto::query()
            ->whereNull('candidato_id')
            ->where('estado', 'activo')
            ->orderBy('orden')
            ->orderBy('id')
            ->get(['id', 'nombre']);

        return $this->successResponse(
            $niveles,
            'Niveles del puesto obtenidos correctamente'
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

    public function update(
        ActualizarVacanteRequest $request,
        Empleo $vacante
    ): JsonResponse {
        $empresa = $this->empresaAutenticada($request);

        $vacante = $this->vacanteService->actualizar(
            $empresa,
            $vacante,
            $request->validated()
        );

        return $this->successResponse(
            new VacanteEmpresaResource($vacante),
            'Vacante actualizada. Quedó pendiente de revisión.'
        );
    }

    public function pausar(Request $request, Empleo $vacante): JsonResponse
    {
        $vacante = $this->gestionService->pausar(
            $this->empresaAutenticada($request),
            $vacante
        );

        return $this->successResponse(
            new VacanteEmpresaResource($vacante),
            'Vacante pausada. Ya no recibe postulaciones.'
        );
    }

    public function reanudar(Request $request, Empleo $vacante): JsonResponse
    {
        $vacante = $this->gestionService->reanudar(
            $this->empresaAutenticada($request),
            $vacante
        );

        return $this->successResponse(
            new VacanteEmpresaResource($vacante),
            'Vacante reanudada.'
        );
    }

    public function finalizar(Request $request, Empleo $vacante): JsonResponse
    {
        $vacante = $this->gestionService->finalizar(
            $this->empresaAutenticada($request),
            $vacante
        );

        return $this->successResponse(
            new VacanteEmpresaResource($vacante),
            'Vacante finalizada. Esta acción es definitiva.'
        );
    }

    public function destroy(Request $request, Empleo $vacante): JsonResponse
    {
        $vacante = $this->gestionService->eliminar(
            $this->empresaAutenticada($request),
            $vacante
        );

        return $this->successResponse(
            new VacanteEmpresaResource($vacante),
            'Vacante eliminada. Puedes consultarla en el historial.'
        );
    }

    public function eliminadas(Request $request): JsonResponse
    {
        $vacantes = $this->gestionService->eliminadas(
            $this->empresaAutenticada($request)
        );

        return $this->successResponse(
            VacanteEmpresaResource::collection($vacantes),
            'Historial de vacantes eliminadas'
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