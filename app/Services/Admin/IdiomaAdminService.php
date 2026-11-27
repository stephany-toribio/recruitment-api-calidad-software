<?php

namespace App\Services\Admin;

use App\Exceptions\BusinessException;
use App\Models\Idioma;
use Illuminate\Database\Eloquent\Collection;

// Los idiomas no tienen valores personalizados: el candidato solo elige del catálogo (HU-01, catálogo normalizado)
class IdiomaAdminService
{
    /**
     * @param array{activo?: bool} $filtros
     */
    public function listar(array $filtros): Collection
    {
        return Idioma::query()
            ->when(array_key_exists('activo', $filtros), fn ($query) => $query->where('activo', (bool) $filtros['activo']))
            ->orderBy('nombre')
            ->get();
    }

    public function crear(array $datos): Idioma
    {
        return Idioma::create([
            'nombre' => $datos['nombre'],
            'activo' => true,
        ]);
    }

    public function actualizar(Idioma $idioma, array $datos): Idioma
    {
        $idioma->update($datos);

        return $idioma->fresh();
    }

    public function desactivar(Idioma $idioma): Idioma
    {
        if (! $idioma->activo) {
            throw new BusinessException(
                'El idioma ya se encuentra inactivo.',
                409,
                'IDIOMA_ALREADY_INACTIVE'
            );
        }

        $idioma->update(['activo' => false]);

        return $idioma->fresh();
    }

    public function reactivar(Idioma $idioma): Idioma
    {
        if ($idioma->activo) {
            throw new BusinessException(
                'El idioma ya se encuentra activo.',
                409,
                'IDIOMA_ALREADY_ACTIVE'
            );
        }

        $idioma->update(['activo' => true]);

        return $idioma->fresh();
    }
}
