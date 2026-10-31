<?php

namespace App\Services\Candidatos;

use App\Exceptions\BusinessException;
use App\Models\Candidato;
use App\Models\Empleo;
use App\Models\PagoVacante;
use App\Models\VacanteGuardada;
use App\Services\Vacantes\DisponibilidadVacanteService;
use Illuminate\Database\Eloquent\Collection;

class VacanteGuardadaService
{
    public function __construct(
        private DisponibilidadVacanteService $disponibilidad
    ) {}

    // Solo las que siguen disponibles; las pausadas, finalizadas o eliminadas no se muestran
    public function listar(Candidato $candidato): Collection
    {
        $publicadaEn = PagoVacante::query()
            ->selectRaw('MIN(pagos_vacante.created_at)')
            ->whereColumn('pagos_vacante.empleo_id', 'empleos.id')
            ->where('pagos_vacante.resultado', PagoVacante::EXITOSO);

        return $this->disponibilidad->aplicar(Empleo::query())
            ->join('vacantes_guardadas', 'vacantes_guardadas.empleo_id', '=', 'empleos.id')
            ->where('vacantes_guardadas.candidato_id', $candidato->getKey())
            ->select('empleos.*')
            ->addSelect(['publicada_en' => $publicadaEn])
            ->withCount('postulaciones')
            ->with([
                'empresa:id,razon_social,sector',
                'nivelPuesto:id,nombre',
                'areaEstudio:id,nombre',
                'habilidades:id,nombre,tipo_habilidad',
            ])
            ->orderByDesc('vacantes_guardadas.created_at')
            ->orderByDesc('vacantes_guardadas.id')
            ->get();
    }

    // Devuelve true si se guardó ahora y false si ya estaba guardada
    public function guardar(Candidato $candidato, int $vacanteId): bool
    {
        $disponible = $this->disponibilidad->aplicar(Empleo::query())
            ->whereKey($vacanteId)
            ->exists();

        if (! $disponible) {
            throw new BusinessException(
                'La vacante no existe o ya no está disponible.',
                404,
                'VACANCY_NOT_AVAILABLE'
            );
        }

        return VacanteGuardada::createOrFirst([
            'candidato_id' => $candidato->getKey(),
            'empleo_id' => $vacanteId,
        ])->wasRecentlyCreated;
    }

    public function quitar(Candidato $candidato, int $vacanteId): void
    {
        $candidato->vacantesGuardadas()
            ->where('empleo_id', $vacanteId)
            ->delete();
    }
}
