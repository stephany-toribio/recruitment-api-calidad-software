<?php

namespace App\Services\Vacantes;

use App\Models\Empleo;
use App\Models\PagoVacante;
use App\Models\RevisionVacante;
use Illuminate\Database\Eloquent\Builder;

class DisponibilidadVacanteService
{
    public function aplicar(Builder $consulta): Builder
    {
        $hoy = now(Empleo::ZONA_VIGENCIA)->toDateString();

        return $this->publicadaDeEmpresaActiva(
            $consulta
                ->whereNull('empleos.deleted_at')
                ->where('empleos.estado_empleo', Empleo::ACTIVA)
                ->where('empleos.fecha_inicio_vigencia', '<=', $hoy)
                ->where('empleos.fecha_fin_vigencia', '>=', $hoy)
        );
    }

    // Vacantes que se publicaron y ya no reciben postulaciones: pausadas, finalizadas o eliminadas, de empresa activa.
    // Cualquier candidato puede ver su detalle en modo histórico. Las que nunca se publicaron siguen ocultas
    public function aplicarHistorico(Builder $consulta): Builder
    {
        return $this->publicadaDeEmpresaActiva(
            $consulta
                ->withTrashed()
                ->where(
                    fn (Builder $estado) => $estado
                        ->whereNotNull('empleos.deleted_at')
                        ->orWhereIn('empleos.estado_empleo', [
                            Empleo::PAUSADA,
                            Empleo::FINALIZADA,
                        ])
                )
        );
    }

    // Aprobada (revisión vigente), con pago exitoso y de una empresa con la cuenta activa
    private function publicadaDeEmpresaActiva(Builder $consulta): Builder
    {
        return $consulta
            ->whereHas(
                'revision',
                fn (Builder $revision) => $revision->where(
                    'revisiones_vacante.decision',
                    RevisionVacante::APROBADA
                )
            )
            ->whereHas(
                'pagos',
                fn (Builder $pagos) => $pagos->where(
                    'pagos_vacante.resultado',
                    PagoVacante::EXITOSO
                )
            )
            ->whereHas(
                'empresa.usuario',
                fn (Builder $usuarios) => $usuarios->where(
                    'usuarios.estado_cuenta',
                    'activo'
                )
            );
    }

    public function estaDisponible(Empleo $vacante): bool
    {
        if (! $vacante->exists || $vacante->getKey() === null) {
            return false;
        }

        return $this->aplicar(Empleo::query())
            ->whereKey($vacante->getKey())
            ->exists();
    }
}
