<?php

namespace App\Services\Vacantes;

use App\Exceptions\BusinessException;
use App\Models\Empleo;
use App\Models\PagoVacante;
use App\Models\RevisionVacante;
use Illuminate\Support\Facades\DB;

class PagoVacanteService
{
    private const RELACIONES_EMPRESA = ['nivelPuesto', 'areaEstudio', 'habilidades', 'revision', 'pagos'];

    /**
     * @return array<string, mixed>
     */
    public function resumen(Empleo $vacante, int $usuarioId): array
    {
        $this->verificarAcceso($vacante, $usuarioId);

        $vacante->load(['revision', 'pagos' => fn ($query) => $query->orderByDesc('id')]);

        return [
            'vacante_id' => $vacante->id,
            'estado_pago' => PagoVacante::estadoDe($vacante->pagos),
            'puede_pagar' => $this->estaAprobada($vacante) && ! $this->tienePagoExitoso($vacante),
            'tarifa_por_puesto' => (float) config('pago_vacantes.tarifa_por_puesto'),
            'unidad_cobro' => config('pago_vacantes.unidad_cobro'),
            'max_contrataciones' => $vacante->numero_vacantes,
            'monto' => $this->calcularMonto($vacante),
            'moneda' => config('pago_vacantes.moneda'),
            'intentos' => $vacante->pagos,
        ];
    }

    /**
     * @return array{pago: PagoVacante, vacante: Empleo}
     */
    public function pagar(Empleo $vacante, int $usuarioId, array $datos): array
    {
        return $this->registrarIntento($vacante, $usuarioId, PagoVacante::EXITOSO, substr($datos['numero_tarjeta'], -4));
    }

    /**
     * Todas las verificaciones van antes del insert: si alguna falla no se registra nada.
     * Después del insert no se lanza ninguna excepción, para no perder el intento.
     *
     * @return array{pago: PagoVacante, vacante: Empleo}
     */
    private function registrarIntento(Empleo $vacante, int $usuarioId, string $resultado, ?string $ultimos4): array
    {
        return DB::transaction(function () use ($vacante, $usuarioId, $resultado, $ultimos4) {
            // Bloqueo de lectura de la vacante: un pago no se cruza con otro pago ni con una eliminación
            $bloqueada = Empleo::withTrashed()->whereKey($vacante->getKey())->lockForUpdate()->firstOrFail();

            $this->verificarAcceso($bloqueada, $usuarioId);

            if (! $this->estaAprobada($bloqueada)) {
                throw new BusinessException('La vacante debe estar aprobada para realizar el pago.', 409, 'VACANCY_NOT_APPROVED');
            }

            if ($this->tienePagoExitoso($bloqueada)) {
                throw new BusinessException('La vacante ya tiene un pago exitoso.', 409, 'VACANCY_ALREADY_PAID');
            }

            $pago = PagoVacante::create([
                'empleo_id' => $bloqueada->id,
                'resultado' => $resultado,
                'monto' => $this->calcularMonto($bloqueada),
                'moneda' => config('pago_vacantes.moneda'),
                'tarjeta_ultimos4' => $ultimos4,
            ]);

            return [
                'pago' => $pago,
                'vacante' => $bloqueada->load(self::RELACIONES_EMPRESA),
            ];
        });
    }

    // Primero la propiedad (403) y después la eliminación (409): a otra empresa no se le revela el estado
    private function verificarAcceso(Empleo $vacante, int $usuarioId): void
    {
        $vacante->loadMissing('empresa');

        if ((int) $vacante->empresa?->usuario_id !== $usuarioId) {
            throw new BusinessException('No tienes permisos para realizar esta acción.', 403, 'FORBIDDEN');
        }

        if ($vacante->trashed()) {
            throw new BusinessException('La vacante fue eliminada y no se puede modificar.', 409, 'VACANCY_DELETED');
        }
    }

    private function estaAprobada(Empleo $vacante): bool
    {
        return RevisionVacante::estadoDe($vacante->revision) === RevisionVacante::APROBADA;
    }

    private function tienePagoExitoso(Empleo $vacante): bool
    {
        return $vacante->pagos()->where('resultado', PagoVacante::EXITOSO)->exists();
    }

    // 'puesto' → tarifa × max_contrataciones · 'vacante' → tarifa (un solo cobro por anuncio)
    private function calcularMonto(Empleo $vacante): float
    {
        $tarifa = (float) config('pago_vacantes.tarifa_por_puesto');

        if (config('pago_vacantes.unidad_cobro') === 'vacante') {
            return round($tarifa, 2);
        }

        return round($tarifa * $vacante->numero_vacantes, 2);
    }
}
