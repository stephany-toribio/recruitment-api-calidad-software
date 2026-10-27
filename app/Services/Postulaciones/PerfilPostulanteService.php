<?php

namespace App\Services\Postulaciones;

use App\Exceptions\BusinessException;
use App\Models\Empresa;
use App\Models\Postulacion;
use App\Services\CandidatoService;

class PerfilPostulanteService{
    
    public function __construct(
        private CandidatoService $candidatoService
    ) {}

    public function obtener(Empresa $empresa, int $postulacionId): Postulacion {

        //No existe  404
        $postulacion=Postulacion::with(['empleo.habilidades', 'candidato', 'estado'])
                ->findOrFail($postulacionId);

        //A otra empresa no se le revela si la vacante esta eliminada o retirada
        if((int)$postulacion->empleo->empresa_id !== $empresa->id){
            throw new BusinessException(
                'No tienes permisos para realizar esta acción.',
                403,
                'FORBIDDEN'
            );
        }

        //vacante eliminada
        if ($postulacion->empleo->trashed()){
            throw new BusinessException(
                'La vacante fue eliminada.',
                409,
                'VACANCY_DELETED'
            );
        }

        //postulante retirado
        if ($postulacion->estaRetirada()){
            throw new BusinessException(
                'El postulante se retiró del proceso.',
                409,
                'APPLICATION_WITHDRAWN'
            );
        }

        //perfil completo con las relaciones de CandidatoService
        $this->candidatoService->cargarPerfil($postulacion->candidato);

        return $postulacion;
    }

}