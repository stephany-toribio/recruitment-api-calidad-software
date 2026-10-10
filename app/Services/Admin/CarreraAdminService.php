<?php

namespace App\Services\Admin;

use App\Exceptions\BusinessException;
use App\Models\Carrera;
use Illuminate\Database\Eloquent\Collection;

// Las carreras no tienen valores personalizados: el candidato solo elige del catálogo (HU-01, sin texto libre)
class CarreraAdminService
{
    /**
     * @param array{tipo_estudio_id?: int, activo?: bool} $filtros
     */
    public function listar(array $filtros): Collection
    {
        return Carrera::query()
            ->with('tipoEstudio')
            ->when($filtros['tipo_estudio_id'] ?? null, fn ($query, $tipoId) => $query->where('tipo_estudio_id', $tipoId))
            ->when(array_key_exists('activo', $filtros), fn ($query) => $query->where('activo', (bool) $filtros['activo']))
            ->orderBy('nombre')
            ->get();
    }

    public function crear(array $datos): Carrera
    {
        return Carrera::create([
            'nombre'          => $datos['nombre'],
            'tipo_estudio_id' => $datos['tipo_estudio_id'],
            'activo'          => true,
        ])->load('tipoEstudio');
    }

    public function actualizar(Carrera $carrera, array $datos): Carrera
    {
        $carrera->update($datos);

        return $carrera->fresh('tipoEstudio');
    }

    public function desactivar(Carrera $carrera): Carrera
    {
        if (! $carrera->activo) {
            throw new BusinessException(
                'La carrera ya se encuentra inactiva.',
                409,
                'CARRERA_ALREADY_INACTIVE'
            );
        }

        $carrera->update(['activo' => false]);

        return $carrera->fresh('tipoEstudio');
    }

    public function reactivar(Carrera $carrera): Carrera
    {
        if ($carrera->activo) {
            throw new BusinessException(
                'La carrera ya se encuentra activa.',
                409,
                'CARRERA_ALREADY_ACTIVE'
            );
        }

        $carrera->update(['activo' => true]);

        return $carrera->fresh('tipoEstudio');
    }
}
