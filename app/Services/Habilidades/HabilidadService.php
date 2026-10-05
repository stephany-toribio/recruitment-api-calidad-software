<?php

namespace App\Services\Habilidades;

use App\Exceptions\BusinessException;
use App\Models\Habilidad;
use Illuminate\Database\Eloquent\Collection;

class HabilidadService
{
    public function listarTodas(): Collection
    {
        return Habilidad::query()
            ->with('areaEstudio')
            ->orderBy('nombre')
            ->get();
    }

    
    public function listarSeleccionables(array $areas = []): Collection
    {
        return Habilidad::query()
            ->with('areaEstudio')
            ->where('activo', true)
            ->where(function ($query) use ($areas) {
                $query
                    ->where('tipo_habilidad', 'blanda')
                    ->orWhere(function ($query) use ($areas) {
                        $query
                            ->where('tipo_habilidad', 'tecnica')
                            ->when($areas, fn ($query) => $query->whereIn('area_estudio_id', $areas))
                            ->whereHas('areaEstudio', function ($query) {
                                $query->where('activo', true);
                            });
                    });
            })
            ->orderBy('nombre')
            ->get();
    }

    public function crear(array $datos): Habilidad
    {
        return Habilidad::create([
            'nombre' => $datos['nombre'],
            'tipo_habilidad' => $datos['tipo_habilidad'],
            'area_estudio_id' => $datos['area_estudio_id'] ?? null,
            'activo' => true,
        ])->load('areaEstudio');
    }

    public function actualizar(
        Habilidad $habilidad,
        array $datos
    ): Habilidad {
        $habilidad->update($datos);

        return $habilidad->fresh()->load('areaEstudio');
    }

    public function desactivar(Habilidad $habilidad): Habilidad
    {
        if (!$habilidad->activo) {
            throw new BusinessException(
                'La habilidad ya se encuentra inactiva.',
                409,
                'SKILL_ALREADY_INACTIVE'
            );
        }

        $habilidad->update([
            'activo' => false,
        ]);

        return $habilidad->fresh()->load('areaEstudio');
    }

    public function reactivar(Habilidad $habilidad): Habilidad
    {
        if ($habilidad->activo) {
            throw new BusinessException(
                'La habilidad ya se encuentra activa.',
                409,
                'SKILL_ALREADY_ACTIVE'
            );
        }

        if (
            $habilidad->tipo_habilidad === 'tecnica'
            && (
                !$habilidad->areaEstudio
                || !$habilidad->areaEstudio->activo
            )
        ) {
            throw new BusinessException(
                'No se puede reactivar la habilidad técnica porque su área de estudio se encuentra inactiva o no existe.',
                409,
                'SKILL_AREA_INACTIVE'
            );
        }

        $habilidad->update([
            'activo' => true,
        ]);

        return $habilidad->fresh()->load('areaEstudio');
    }
}
