<?php

namespace App\Services\Carreras;

use App\Models\Carrera;
use Illuminate\Database\Eloquent\Collection;

class CarreraService
{
    /**
     * Carreras seleccionables con los filtros que lleguen. Dependen del tipo de estudio y del país (no del área).
     *
     * @param array{tipo_estudio_id?: int, pais_id?: int} $filtros
     */
    public function listarSeleccionables(array $filtros): Collection
    {
        return Carrera::query()
            ->when($filtros['tipo_estudio_id'] ?? null, fn ($query, $id) => $query->where('tipo_estudio_id', $id))
            ->when($filtros['pais_id'] ?? null, fn ($query, $id) => $query->where('pais_id', $id))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'tipo_estudio_id', 'pais_id']);
    }
}
