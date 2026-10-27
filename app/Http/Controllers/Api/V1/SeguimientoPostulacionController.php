<?php
namespace App\Http\Controllers\Api\V1;

use App\Exceptions\BusinessException;
use App\Http\Resources\Api\V1\PerfilPostulanteResource;
use App\Models\Empresa;
use App\Services\Postulaciones\PerfilPostulanteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SeguimientoPostulacionController extends BaseApiController{
    
    public function __construct(
        private PerfilPostulanteService $perfilPostulanteService
    ) {}

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
                'No tienes permisos para realizar esta accion',
                403,
                'FORBIDDEN'
            );
        }

        return $empresa;
    } 
}