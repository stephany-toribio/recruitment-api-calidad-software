<?php

namespace App\Services\Carreras;

use App\Models\Carrera;
use Illuminate\Database\Eloquent\Collection;

class CarreraService
{
    /**
     * Carreras seleccionables. Son globales (las mismas en todos los países) y dependen solo del tipo de estudio.
     *
     * @param array{tipo_estudio_id?: int} $filtros
     */
    public function listarSeleccionables(array $filtros): Collection
    {
        return Carrera::query()
            ->when($filtros['tipo_estudio_id'] ?? null, fn ($query, $id) => $query->where('tipo_estudio_id', $id))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'tipo_estudio_id']);
    }
}
