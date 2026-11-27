<?php

namespace App\Services\Admin;

use App\Exceptions\BusinessException;
use App\Models\Institucion;
use Illuminate\Database\Eloquent\Collection;

class InstitucionAdminService
{
    public function listar(): Collection
    {
        return Institucion::query()
            ->with('pais')
            ->orderBy('nombre')
            ->get();
    }

    public function crear(array $datos): Institucion
    {
        return Institucion::create([
            'nombre'  => $datos['nombre'],
            'pais_id' => $datos['pais_id'],
            'estado'  => 'activo',
        ])->load('pais');
    }

    public function actualizar(Institucion $institucion, array $datos): Institucion
    {
        $institucion->update($datos);

        return $institucion->fresh('pais');
    }

    public function desactivar(Institucion $institucion): Institucion
    {
        if ($institucion->estado === 'inactivo') {
            throw new BusinessException(
                'La institución ya se encuentra inactiva.',
                409,
                'INSTITUCION_ALREADY_INACTIVE'
            );
        }

        $institucion->update(['estado' => 'inactivo']);

        return $institucion->fresh('pais');
    }

    public function reactivar(Institucion $institucion): Institucion
    {
        if ($institucion->estado === 'activo') {
            throw new BusinessException(
                'La institución ya se encuentra activa.',
                409,
                'INSTITUCION_ALREADY_ACTIVE'
            );
        }

        $institucion->loadMissing('pais');

        if (!$institucion->pais || !$institucion->pais->activo) {
            throw new BusinessException(
                'No se puede reactivar la institución porque su país se encuentra inactivo.',
                409,
                'INSTITUCION_PAIS_INACTIVO'
            );
        }

        $institucion->update(['estado' => 'activo']);

        return $institucion->fresh('pais');
    }
}
