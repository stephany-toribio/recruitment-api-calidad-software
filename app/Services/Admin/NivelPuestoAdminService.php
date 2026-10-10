<?php

namespace App\Services\Admin;

use App\Exceptions\BusinessException;
use App\Models\NivelPuesto;
use Illuminate\Database\Eloquent\Collection;

/**
 * Igual que los puestos, más el orden jerárquico de los globales.
 */
class NivelPuestoAdminService
{
    /**
     * Globales en su orden jerárquico y después los personalizados (sin orden), por nombre.
     *
     * @param array{tipo?: string, estado?: string} $filtros
     */
    public function listar(array $filtros): Collection
    {
        return NivelPuesto::query()
            ->with('creador')
            // Personalizados con el mismo nombre (de distintos candidatos)
            ->addSelect(['candidatos_con_mismo_nombre' => NivelPuesto::from('niveles_puesto as mismos')
                ->selectRaw('COUNT(*)')
                ->whereColumn('mismos.nombre', 'niveles_puesto.nombre')
                ->whereNotNull('mismos.candidato_id'),
            ])
            ->when(($filtros['tipo'] ?? null) === 'global', fn ($query) => $query->whereNull('candidato_id'))
            ->when(($filtros['tipo'] ?? null) === 'personalizado', fn ($query) => $query->whereNotNull('candidato_id'))
            ->when($filtros['estado'] ?? null, fn ($query, $estado) => $query->where('estado', $estado))
            ->orderByRaw('orden IS NULL')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->orderBy('id')
            ->get();
    }

    public function crear(array $datos): NivelPuesto
    {
        // Sin orden: va después del último global
        $orden = $datos['orden']
            ?? (int) NivelPuesto::whereNull('candidato_id')->max('orden') + 1;

        return NivelPuesto::create([
            'nombre' => $datos['nombre'],
            'orden'  => $orden,
            'estado' => 'activo',
        ])->load('creador');
    }

    public function actualizar(NivelPuesto $nivel, array $datos): NivelPuesto
    {
        if ($nivel->esPersonalizado()) {
            throw new BusinessException(
                'Un nivel personalizado no puede editarse: pertenece al candidato que lo escribió.',
                409,
                'NIVEL_PUESTO_PERSONALIZADO_NO_EDITABLE'
            );
        }

        $nivel->update($datos);

        return $nivel->fresh('creador');
    }

    public function desactivar(NivelPuesto $nivel): NivelPuesto
    {
        if ($nivel->estado === 'inactivo') {
            throw new BusinessException(
                'El nivel ya se encuentra inactivo.',
                409,
                'NIVEL_PUESTO_ALREADY_INACTIVE'
            );
        }

        $nivel->update(['estado' => 'inactivo']);

        return $nivel->fresh('creador');
    }

    // Solo lo habilita: un personalizado sigue siendo del candidato y no pasa al catálogo global
    public function reactivar(NivelPuesto $nivel): NivelPuesto
    {
        if ($nivel->estado === 'activo') {
            throw new BusinessException(
                'El nivel ya se encuentra activo.',
                409,
                'NIVEL_PUESTO_ALREADY_ACTIVE'
            );
        }

        $nivel->update(['estado' => 'activo']);

        return $nivel->fresh('creador');
    }
}
