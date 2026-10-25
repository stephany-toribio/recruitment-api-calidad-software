<?php

namespace App\Services\Postulaciones;

use App\Exceptions\BusinessException;
use App\Models\Candidato;
use App\Models\EstadoPostulacion;
use App\Models\Postulacion;
use App\Models\Usuario;
use Illuminate\Support\Facades\DB;
use LogicException;

class RetiroPostulacionService
{
    public function retirar(
        Candidato $candidato,
        int $postulacionId
    ): Postulacion {
        if (
            ! $candidato->exists
            || $candidato->getKey() === null
            || $candidato->usuario_id === null
        ) {
            throw $this->sinPermiso();
        }

        return DB::transaction(function () use (
            $candidato,
            $postulacionId
        ): Postulacion {
            $usuario = Usuario::query()
                ->whereKey($candidato->usuario_id)
                ->lockForUpdate()
                ->first();

            if (
                $usuario === null
                || $usuario->estado_cuenta !== 'activo'
                || $usuario->rol?->nombre !== 'candidato'
            ) {
                throw $this->sinPermiso();
            }

            $perfil = Candidato::query()
                ->whereKey($candidato->getKey())
                ->where('usuario_id', $usuario->getKey())
                ->sharedLock()
                ->first();

            if ($perfil === null) {
                throw $this->sinPermiso();
            }

            $postulacion = Postulacion::query()
                ->whereKey($postulacionId)
                ->lockForUpdate()
                ->first();

            if ($postulacion === null) {
                throw new BusinessException(
                    'El recurso solicitado no fue encontrado.',
                    404,
                    'RESOURCE_NOT_FOUND'
                );
            }

            if (
                $postulacion->candidato_id
                !== (int) $perfil->getKey()
            ) {
                throw $this->sinPermiso();
            }

            if ($postulacion->estaRetirada()) {
                throw new BusinessException(
                    'Ya te retiraste de esta postulación.',
                    409,
                    'APPLICATION_ALREADY_WITHDRAWN'
                );
            }

            $postulacion->load('estado:id,nombre');

            $estado = $postulacion->estado;

            if ($estado === null) {
                throw new LogicException(
                    'La postulación no tiene un estado de selección válido.'
                );
            }

            $etapasPermitidas = [
                EstadoPostulacion::RECIBIDO,
                EstadoPostulacion::EN_REVISION,
                EstadoPostulacion::ENTREVISTA,
            ];

            if (
                ! in_array(
                    $estado->nombre,
                    $etapasPermitidas,
                    true
                )
            ) {
                throw new BusinessException(
                    'Solo puedes retirarte desde Recibido, En Revisión o Entrevista.',
                    409,
                    'APPLICATION_NOT_WITHDRAWABLE'
                );
            }

            $postulacion->retirada_en = now();
            $postulacion->save();

            return $postulacion;
        }, 3);
    }

    private function sinPermiso(): BusinessException
    {
        return new BusinessException(
            'No tienes permisos para realizar esta acción.',
            403,
            'FORBIDDEN'
        );
    }
}
