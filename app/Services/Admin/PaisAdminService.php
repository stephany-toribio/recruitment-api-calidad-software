<?php

namespace App\Services\Admin;

use App\Exceptions\BusinessException;
use App\Models\Pais;
use Illuminate\Database\Eloquent\Collection;

class PaisAdminService
{
    public function listar(): Collection
    {
        return Pais::query()
            ->orderBy('nombre')
            ->get();
    }

    public function crear(array $datos): Pais
    {
        return Pais::create([
            'nombre' => $datos['nombre'],
            'activo' => true,
        ]);
    }

    public function actualizar(Pais $pais, array $datos): Pais
    {
        $pais->update($datos);

        return $pais->fresh();
    }

    public function desactivar(Pais $pais): Pais
    {
        if (!$pais->activo) {
            throw new BusinessException(
                'El país ya se encuentra inactivo.',
                409,
                'PAIS_ALREADY_INACTIVE'
            );
        }

        $pais->update(['activo' => false]);

        return $pais->fresh();
    }

    public function reactivar(Pais $pais): Pais
    {
        if ($pais->activo) {
            throw new BusinessException(
                'El país ya se encuentra activo.',
                409,
                'PAIS_ALREADY_ACTIVE'
            );
        }

        $pais->update(['activo' => true]);

        return $pais->fresh();
    }
}
