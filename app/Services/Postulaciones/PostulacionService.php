<?php

namespace App\Services\Postulaciones;

use App\Exceptions\BusinessException;
use App\Exceptions\PostulacionDuplicadaException;
use App\Models\Candidato;
use App\Models\Empleo;
use App\Models\Empresa;
use App\Models\EstadoPostulacion;
use App\Models\Postulacion;
use App\Models\Usuario;
use App\Services\Vacantes\DisponibilidadVacanteService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use LogicException;

class PostulacionService
{
    public function __construct(
        private DisponibilidadVacanteService $disponibilidad
    ) {}

    public function postular(
        Candidato $candidato,
        int $vacanteId
    ): Postulacion {
        if (
            ! $candidato->exists
            || $candidato->getKey() === null
            || $candidato->usuario_id === null
        ) {
            throw $this->sinPermiso();
        }

        try {
            return DB::transaction(function () use (
                $candidato,
                $vacanteId
            ): Postulacion {
                $vacante = Empleo::withTrashed()
                    ->whereKey($vacanteId)
                    ->lockForUpdate()
                    ->first();

                if ($vacante === null) {
                    throw $this->vacanteNoDisponible();
                }

                $empresa = Empresa::query()
                    ->whereKey($vacante->empresa_id)
                    ->first();

                if ($empresa === null) {
                    throw $this->vacanteNoDisponible();
                }

                $usuarios = Usuario::query()
                    ->whereIn('id', [
                        $candidato->usuario_id,
                        $empresa->usuario_id,
                    ])
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('id');

                $usuarioCandidato = $usuarios->get(
                    $candidato->usuario_id
                );

                if (
                    $usuarioCandidato === null
                    || $usuarioCandidato->estado_cuenta !== 'activo'
                    || $usuarioCandidato->rol?->nombre !== 'candidato'
                ) {
                    throw $this->sinPermiso();
                }

                $perfil = Candidato::query()
                    ->whereKey($candidato->getKey())
                    ->where(
                        'usuario_id',
                        $usuarioCandidato->getKey()
                    )
                    ->sharedLock()
                    ->first();

                if ($perfil === null) {
                    throw $this->sinPermiso();
                }

                $previa = Postulacion::query()
                    ->where('candidato_id', $perfil->getKey())
                    ->where('empleo_id', $vacante->getKey())
                    ->lockForUpdate()
                    ->first(['id']);

                if ($previa !== null) {
                    throw new PostulacionDuplicadaException;
                }

                $usuarioEmpresa = $usuarios->get(
                    $empresa->usuario_id
                );

                if (
                    $usuarioEmpresa === null
                    || $usuarioEmpresa->estado_cuenta !== 'activo'
                    || ! $this->disponibilidad->estaDisponible($vacante)
                ) {
                    throw $this->vacanteNoDisponible();
                }

                $estadoId = EstadoPostulacion::query()
                    ->where('nombre', EstadoPostulacion::RECIBIDO)
                    ->value('id');

                if ($estadoId === null) {
                    throw new LogicException(
                        'Falta el estado inicial Recibido en el catálogo.'
                    );
                }

                $postulacion = Postulacion::create([
                    'candidato_id' => $perfil->getKey(),
                    'empleo_id' => $vacante->getKey(),
                    'estado_postulacion_id' => $estadoId,
                    'fecha_postulacion' => now(),
                    'retirada_en' => null,
                ]);

                return $postulacion->load([
                    'estado:id,nombre',
                    'empleo:id,titulo',
                ]);
            }, 3);
        } catch (UniqueConstraintViolationException $error) {
            $codigoMySql = (int) ($error->errorInfo[1] ?? 0);
            $detalle = (string) ($error->errorInfo[2] ?? '');

            if (
                $codigoMySql === 1062
                && str_contains(
                    $detalle,
                    'postulaciones_candidato_id_empleo_id_unique'
                )
            ) {
                throw new PostulacionDuplicadaException;
            }

            throw $error;
        }
    }

    private function sinPermiso(): BusinessException
    {
        return new BusinessException(
            'No tienes permisos para realizar esta acción.',
            403,
            'FORBIDDEN'
        );
    }

    private function vacanteNoDisponible(): BusinessException
    {
        return new BusinessException(
            'La vacante no existe o ya no está disponible.',
            404,
            'VACANCY_NOT_AVAILABLE'
        );
    }
}
