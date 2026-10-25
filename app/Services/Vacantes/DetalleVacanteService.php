<?php

namespace App\Services\Vacantes;

use App\Models\Candidato;
use App\Models\Empleo;
use App\Models\PagoVacante;
use App\Models\Postulacion;
use Illuminate\Database\Eloquent\Builder;

class DetalleVacanteService
{
    public function __construct(
        private DisponibilidadVacanteService $disponibilidad
    ) {}

    /**
     * @return array{
     *     vacante: Empleo,
     *     modo_consulta: string,
     *     postulacion: Postulacion|null,
     *     puede_postular: bool,
     *     motivo_no_postulable: string|null
     * }
     */
    public function disponible(
        int $vacanteId,
        Candidato $candidato
    ): array {
        $consulta = $this->disponibilidad
            ->aplicar(Empleo::query())
            ->whereKey($vacanteId);

        $vacante = $this->cargarDetalle($consulta)
            ->firstOrFail();

        $postulacion = Postulacion::query()
            ->where('candidato_id', $candidato->getKey())
            ->where('empleo_id', $vacante->getKey())
            ->first([
                'id',
                'candidato_id',
                'empleo_id',
                'retirada_en',
            ]);

        $motivo = match (true) {
            $postulacion === null => null,
            $postulacion->estaRetirada() => 'APPLICATION_WITHDRAWN',
            default => 'ALREADY_APPLIED',
        };

        return [
            'vacante' => $vacante,
            'modo_consulta' => 'disponible',
            'postulacion' => $postulacion,
            'puede_postular' => $postulacion === null,
            'motivo_no_postulable' => $motivo,
        ];
    }

    /**
     * @return array{
     *     vacante: Empleo,
     *     modo_consulta: string,
     *     postulacion: Postulacion,
     *     puede_postular: bool,
     *     motivo_no_postulable: string
     * }
     */
    public function historico(
        int $postulacionId,
        Candidato $candidato
    ): array {
        $postulacion = Postulacion::query()
            ->whereKey($postulacionId)
            ->where('candidato_id', $candidato->getKey())
            ->firstOrFail([
                'id',
                'candidato_id',
                'empleo_id',
                'retirada_en',
            ]);

        $consulta = Empleo::query()
            ->whereKey($postulacion->empleo_id)
            ->whereNull('empleos.deleted_at')
            ->whereIn('empleos.estado_empleo', [
                Empleo::PAUSADA,
                Empleo::FINALIZADA,
            ])
            ->whereHas(
                'empresa.usuario',
                fn (Builder $consulta) => $consulta
                    ->where('usuarios.estado_cuenta', 'activo')
            );

        $vacante = $this->cargarDetalle($consulta)
            ->firstOrFail();

        return [
            'vacante' => $vacante,
            'modo_consulta' => 'historico',
            'postulacion' => $postulacion,
            'puede_postular' => false,
            'motivo_no_postulable' => 'VACANCY_UNAVAILABLE',
        ];
    }

    private function cargarDetalle(Builder $consulta): Builder
    {
        $publicadaEn = PagoVacante::query()
            ->selectRaw('MIN(pagos_vacante.created_at)')
            ->whereColumn(
                'pagos_vacante.empleo_id',
                'empleos.id'
            )
            ->where(
                'pagos_vacante.resultado',
                PagoVacante::EXITOSO
            );

        return $consulta
            ->select('empleos.*')
            ->addSelect([
                'publicada_en' => $publicadaEn,
            ])
            ->withCount('postulaciones')
            ->with([
                'empresa:id,razon_social,sector,descripcion',
                'nivelPuesto:id,nombre',
                'areaEstudio:id,nombre',
                'habilidades' => fn ($consulta) => $consulta
                    ->select(
                        'habilidades.id',
                        'habilidades.nombre'
                    )
                    ->orderBy('habilidades.nombre')
                    ->orderBy('habilidades.id'),
            ]);
    }
}
