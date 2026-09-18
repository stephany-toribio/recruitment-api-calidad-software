<?php

namespace App\Services\Habilidades;

use App\Models\Habilidad;
use Illuminate\Database\Eloquent\Collection;

class HabilidadService
{
    public function listarActivas(): Collection
    {
        return Habilidad::query()
            ->where('estado', 'activo')
            ->orderBy('nombre')
            ->get();
    }

    public function crear(array $datos): Habilidad
    {
        return Habilidad::create($datos);
    }

    public function actualizar(int $id, array $datos): Habilidad
    {
        $habilidad = Habilidad::findOrFail($id);

        $habilidad->update($datos);

        return $habilidad;
    }
}
