<?php

namespace App\Services\Vacantes;

use App\Exceptions\BusinessException;
use App\Models\Empleo;
use App\Models\Empresa;
use App\Models\PagoVacante;
use App\Models\RevisionVacante;
use Closure;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class GestionVacanteService
{
    private const RELACIONES = [
        'nivelPuesto',
        'areaEstudio',
        'habilidades',
        'revision',
        'pagos',
    ];

    public function pausar(Empresa $empresa, Empleo $vacante): Empleo
    {
        return $this->modificar($empresa, $vacante, function (Empleo $bloqueada) {
            $this->verificarModificable($bloqueada);

            if ($bloqueada->estado_empleo === Empleo::PAUSADA) {
                throw new BusinessException(
                    'La vacante ya está pausada.',
                    409,
                    'VACANCY_ALREADY_PAUSED'
                );
            }

            $this->verificarPublicacion($bloqueada);

            $bloqueada->update([
                'estado_empleo' => Empleo::PAUSADA,
            ]);
        });
    }

    public function reanudar(Empresa $empresa, Empleo $vacante): Empleo
    {
        return $this->modificar($empresa, $vacante, function (Empleo $bloqueada) {
            $this->verificarModificable($bloqueada);

            if ($bloqueada->estado_empleo !== Empleo::PAUSADA) {
                throw new BusinessException(
                    'Solo se puede reanudar una vacante pausada.',
                    409,
                    'VACANCY_NOT_PAUSED'
                );
            }

            $this->verificarPublicacion($bloqueada);

            $hoy = now(Empleo::ZONA_VIGENCIA)->toDateString();

            if ($hoy > $bloqueada->fecha_fin_vigencia->toDateString()) {
                throw new BusinessException(
                    'La vigencia de la vacante venció. No se puede reanudar.',
                    409,
                    'VACANCY_EXPIRED'
                );
            }

            if ($hoy < $bloqueada->fecha_inicio_vigencia->toDateString()) {
                throw new BusinessException(
                    'La vigencia de la vacante aún no ha iniciado. No se puede reanudar.',
                    409,
                    'VACANCY_NOT_STARTED'
                );
            }

            $bloqueada->update([
                'estado_empleo' => Empleo::ACTIVA,
            ]);
        });
    }

    public function finalizar(Empresa $empresa, Empleo $vacante): Empleo
    {
        return $this->modificar($empresa, $vacante, function (Empleo $bloqueada) {
            $this->verificarModificable($bloqueada);
            $this->verificarPublicacion($bloqueada);

            $bloqueada->update([
                'estado_empleo' => Empleo::FINALIZADA,
            ]);
        });
    }

    public function eliminar(Empresa $empresa, Empleo $vacante): Empleo
    {
        return $this->modificar($empresa, $vacante, function (Empleo $bloqueada) {
            if ($bloqueada->trashed()) {
                throw new BusinessException(
                    'La vacante ya está eliminada.',
                    409,
                    'VACANCY_ALREADY_DELETED'
                );
            }

            $bloqueada->delete();
        });
    }

    public function eliminadas(Empresa $empresa): Collection
    {
        return Empleo::onlyTrashed()
            ->where('empresa_id', $empresa->id)
            ->with(self::RELACIONES)
            ->orderByDesc('deleted_at')
            ->orderByDesc('id')
            ->get();
    }

    private function modificar(
        Empresa $empresa,
        Empleo $vacante,
        Closure $accion
    ): Empleo {
        return DB::transaction(function () use ($empresa, $vacante, $accion) {
            $bloqueada = Empleo::withTrashed()
                ->whereKey($vacante->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            // Verificar propiedad antes de revelar el estado de la vacante.
            if ((int) $bloqueada->empresa_id !== (int) $empresa->id) {
                throw new BusinessException(
                    'No tienes permisos para realizar esta acción.',
                    403,
                    'FORBIDDEN'
                );
            }

            $accion($bloqueada);

            return $bloqueada->load(self::RELACIONES);
        });
    }

    private function verificarModificable(Empleo $vacante): void
    {
        if ($vacante->trashed()) {
            throw new BusinessException(
                'La vacante fue eliminada y no se puede modificar.',
                409,
                'VACANCY_DELETED'
            );
        }

        if ($vacante->estado_empleo === Empleo::FINALIZADA) {
            throw new BusinessException(
                'La vacante está finalizada; esta acción ya no está permitida.',
                409,
                'VACANCY_FINALIZED'
            );
        }
    }

    private function verificarPublicacion(Empleo $vacante): void
    {
        $aprobada = $vacante->revision()
            ->where('decision', RevisionVacante::APROBADA)
            ->exists();

        $pagada = $vacante->pagos()
            ->where('resultado', PagoVacante::EXITOSO)
            ->exists();

        if (! $aprobada || ! $pagada) {
            throw new BusinessException(
                'La vacante aún no está publicada (requiere aprobación y pago).',
                409,
                'VACANCY_NOT_PUBLISHED'
            );
        }
    }
}