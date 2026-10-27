<?php
namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BusinessException;
use App\Http\Resources\Api\V1\MiPostulacionResource;
use App\Http\Resources\Api\V1\PerfilPostulanteResource;
use App\Models\Candidato;
use App\Models\Empresa;
use App\Services\Postulaciones\MisPostulacionesService;
use App\Services\Postulaciones\PerfilPostulanteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeguimientoPostulacionController extends BaseApiController{

    public function __construct(
        private PerfilPostulanteService $perfilPostulanteService,
        private MisPostulacionesService $misPostulacionesService
    ) {}

    // HU-15: postulaciones del candidato del token, de la más reciente a la más antigua
    public function misPostulaciones(Request $request): JsonResponse
    {
        return $this->successResponse(
            MiPostulacionResource::collection(
                $this->misPostulacionesService->listar($this->candidatoAutenticado($request))
            ),
            'Postulaciones del candidato'
        );
    }

    public function perfil(Request $request, string $postulacion): JsonResponse
    {
        $postulacion = $this->perfilPostulanteService->obtener(
            $this->empresaAutenticada($request),
            (int) $postulacion
        );

        return $this->successResponse(
            new PerfilPostulanteResource($postulacion),
            'Perfil del postulante'
        );
    }

    //Igual que en Kanban, un usario con rol empresa pero sin perfil de empresa
    private function empresaAutenticada(Request $request): Empresa
    {
        $empresa=$request->user()->empresa;
        
        if($empresa===null){
            throw new BusinessException(
                'No tienes permisos para realizar esta acción.',
                403,
                'FORBIDDEN'
            );
        }

        return $empresa;
    }

    //Igual que en PostulacionController: un usuario con rol candidato pero sin perfil de candidato
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