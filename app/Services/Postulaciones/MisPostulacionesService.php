<?php

namespace App\Services\Postulaciones;

use App\Models\Candidato;
use App\Models\Empleo;
use App\Models\Postulacion;
use App\Services\Vacantes\DisponibilidadVacanteService;
use Illuminate\Database\Eloquent\Collection;

class MisPostulacionesService
{
    public function __construct(
        private DisponibilidadVacanteService $disponibilidad
    ) {}

    public function listar(Candidato $candidato): Collection
    {
        // estado y empleo.empresa precargados
        $postulaciones = $candidato->postulaciones()
            ->with(['estado', 'empleo.empresa'])
            ->orderByDesc('fecha_postulacion')
            ->orderByDesc('id')
            ->get();

        $ids = $postulaciones->pluck('empleo_id')->unique()->values();


        $disponibles = $this->disponibilidad
            ->aplicar(Empleo::query()->whereIn('empleos.id', $ids))
            ->pluck('empleos.id');

        $historicas = $this->disponibilidad
            ->aplicarHistorico(Empleo::query()->whereIn('empleos.id', $ids))
            ->pluck('empleos.id');

        return $postulaciones->each(fn (Postulacion $p) => $p->setAttribute('ver_vacante', match (true) {
            $disponibles->contains($p->empleo_id) => 'disponible',
            $historicas->contains($p->empleo_id)  => 'historico',
            default                               => null, // eliminada, vencida, empresa suspendida…
        }));
    }
}
