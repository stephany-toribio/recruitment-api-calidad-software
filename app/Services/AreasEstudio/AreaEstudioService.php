<?php

namespace App\Services\AreasEstudio;

use App\Exceptions\BusinessException;
use App\Models\AreaEstudio;
use Illuminate\Database\Eloquent\Collection;

class AreaEstudioService
{
    public function listarTodas(): Collection
    {
        return AreaEstudio::query()
            ->orderBy('nombre')
            ->get();
    }

    public function listarActivas(): Collection
    {
        return AreaEstudio::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
    }

    public function crear(array $datos): AreaEstudio
    {
        return AreaEstudio::create([
            'nombre' => $datos['nombre'],
            'activo' => true,
        ]);
    }

    public function actualizar(
        AreaEstudio $areaEstudio,
        array $datos
    ): AreaEstudio {
        $areaEstudio->update($datos);

        return $areaEstudio->fresh();
    }

    public function desactivar(AreaEstudio $areaEstudio): AreaEstudio
    {
        if (!$areaEstudio->activo) {
            throw new BusinessException(
                'El área de estudio ya se encuentra inactiva.',
                409,
                'AREA_ALREADY_INACTIVE'
            );
        }

        $areaEstudio->update([
            'activo' => false,
        ]);

        return $areaEstudio->fresh();
    }

    public function reactivar(AreaEstudio $areaEstudio): AreaEstudio
    {
        if ($areaEstudio->activo) {
            throw new BusinessException(
                'El área de estudio ya se encuentra activa.',
                409,
                'AREA_ALREADY_ACTIVE'
            );
        }

        $areaEstudio->update([
            'activo' => true,
        ]);

        return $areaEstudio->fresh();
    }
}
