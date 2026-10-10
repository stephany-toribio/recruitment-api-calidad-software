<?php

namespace App\Services\Admin;

use App\Exceptions\BusinessException;
use App\Models\Puesto;
use Illuminate\Database\Eloquent\Collection;

/**
 * Catálogo global: el administrador lo crea, edita, deshabilita y habilita.
 * Personalizados (los escribe cada candidato): el administrador solo los ve y los deshabilita o habilita;
 * no los edita ni los convierte en globales (HU-01).
 */
class PuestoAdminService
{
    /**
     * @param array{tipo?: string, estado?: string} $filtros
     */
    public function listar(array $filtros): Collection
    {
        return Puesto::query()
            ->with('creador')
            // Personalizados con el mismo nombre (de distintos candidatos)
            ->addSelect(['candidatos_con_mismo_nombre' => Puesto::from('puestos as mismos')
                ->selectRaw('COUNT(*)')
                ->whereColumn('mismos.nombre', 'puestos.nombre')
                ->whereNotNull('mismos.candidato_id'),
            ])
            ->when(($filtros['tipo'] ?? null) === 'global', fn ($query) => $query->whereNull('candidato_id'))
            ->when(($filtros['tipo'] ?? null) === 'personalizado', fn ($query) => $query->whereNotNull('candidato_id'))
            ->when($filtros['estado'] ?? null, fn ($query, $estado) => $query->where('estado', $estado))
            ->orderBy('nombre')
            ->orderBy('id')
            ->get();
    }

    public function crear(array $datos): Puesto
    {
        return Puesto::create([
            'nombre' => $datos['nombre'],
            'estado' => 'activo',
        ])->load('creador');
    }

    public function actualizar(Puesto $puesto, array $datos): Puesto
    {
        if ($puesto->esPersonalizado()) {
            throw new BusinessException(
                'Un puesto personalizado no puede editarse: pertenece al candidato que lo escribió.',
                409,
                'PUESTO_PERSONALIZADO_NO_EDITABLE'
            );
        }

        $puesto->update($datos);

        return $puesto->fresh('creador');
    }

    public function desactivar(Puesto $puesto): Puesto
    {
        if ($puesto->estado === 'inactivo') {
            throw new BusinessException(
                'El puesto ya se encuentra inactivo.',
                409,
                'PUESTO_ALREADY_INACTIVE'
            );
        }

        $puesto->update(['estado' => 'inactivo']);

        return $puesto->fresh('creador');
    }

    // Solo lo habilita: un personalizado sigue siendo del candidato y no pasa al catálogo global
    public function reactivar(Puesto $puesto): Puesto
    {
        if ($puesto->estado === 'activo') {
            throw new BusinessException(
                'El puesto ya se encuentra activo.',
                409,
                'PUESTO_ALREADY_ACTIVE'
            );
        }

        $puesto->update(['estado' => 'activo']);

        return $puesto->fresh('creador');
    }
}
