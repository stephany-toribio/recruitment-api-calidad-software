<?php

namespace App\Services\Carreras;

use App\Models\Carrera;
use Illuminate\Database\Eloquent\Collection;

class CarreraService
{
    /**
     * Carreras seleccionables: solo las de áreas de estudio activas, con los filtros que lleguen.
     *
     * @param array{tipo_estudio_id?: int, area_estudio_id?: int, pais_id?: int} $filtros
     */
    public function listarSeleccionables(array $filtros): Collection
    {
        return Carrera::query()
            ->whereHas('areaEstudio', fn ($query) => $query->where('activo', true))
            ->when($filtros['tipo_estudio_id'] ?? null, fn ($query, $id) => $query->where('tipo_estudio_id', $id))
            ->when($filtros['area_estudio_id'] ?? null, fn ($query, $id) => $query->where('area_estudio_id', $id))
            ->when($filtros['pais_id'] ?? null, fn ($query, $id) => $query->where('pais_id', $id))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'tipo_estudio_id', 'area_estudio_id', 'pais_id']);
    }
}
