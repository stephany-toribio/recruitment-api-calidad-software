<?php

namespace App\Services\Admin;

use App\Exceptions\BusinessException;
use App\Models\Organizacion;
use Illuminate\Database\Eloquent\Collection;

/**
 * Igual que los puestos, pero cada organización pertenece a un país.
 * Catálogo global: el administrador lo crea, edita, deshabilita y habilita.
 * Personalizadas (las escribe cada candidato): el administrador solo las ve y las deshabilita o habilita;
 * no las edita ni las convierte en globales (HU-01).
 */
class OrganizacionAdminService
{
    private const RELACIONES = ['pais', 'creador'];

    /**
     * @param array{tipo?: string, estado?: string, pais_id?: int} $filtros
     */
    public function listar(array $filtros): Collection
    {
        return Organizacion::query()
            ->with(self::RELACIONES)
            // Personalizadas con el mismo nombre en el mismo país (de distintos candidatos)
            ->addSelect(['candidatos_con_mismo_nombre' => Organizacion::from('organizaciones as mismas')
                ->selectRaw('COUNT(*)')
                ->whereColumn('mismas.nombre', 'organizaciones.nombre')
                ->whereColumn('mismas.pais_id', 'organizaciones.pais_id')
                ->whereNotNull('mismas.candidato_id'),
            ])
            ->when(($filtros['tipo'] ?? null) === 'global', fn ($query) => $query->whereNull('candidato_id'))
            ->when(($filtros['tipo'] ?? null) === 'personalizado', fn ($query) => $query->whereNotNull('candidato_id'))
            ->when($filtros['estado'] ?? null, fn ($query, $estado) => $query->where('estado', $estado))
            ->when($filtros['pais_id'] ?? null, fn ($query, $paisId) => $query->where('pais_id', $paisId))
            ->orderBy('nombre')
            ->orderBy('id')
            ->get();
    }

    public function crear(array $datos): Organizacion
    {
        return Organizacion::create([
            'nombre'  => $datos['nombre'],
            'pais_id' => $datos['pais_id'],
            'estado'  => 'activo',
        ])->load(self::RELACIONES);
    }

    public function actualizar(Organizacion $organizacion, array $datos): Organizacion
    {
        if ($organizacion->esPersonalizada()) {
            throw new BusinessException(
                'Una organización personalizada no puede editarse: pertenece al candidato que la escribió.',
                409,
                'ORGANIZACION_PERSONALIZADA_NO_EDITABLE'
            );
        }

        $organizacion->update($datos);

        return $organizacion->fresh(self::RELACIONES);
    }

    public function desactivar(Organizacion $organizacion): Organizacion
    {
        if ($organizacion->estado === 'inactivo') {
            throw new BusinessException(
                'La organización ya se encuentra inactiva.',
                409,
                'ORGANIZACION_ALREADY_INACTIVE'
            );
        }

        $organizacion->update(['estado' => 'inactivo']);

        return $organizacion->fresh(self::RELACIONES);
    }

    // Solo la habilita: una personalizada sigue siendo del candidato y no pasa al catálogo global
    public function reactivar(Organizacion $organizacion): Organizacion
    {
        if ($organizacion->estado === 'activo') {
            throw new BusinessException(
                'La organización ya se encuentra activa.',
                409,
                'ORGANIZACION_ALREADY_ACTIVE'
            );
        }

        $organizacion->loadMissing('pais');

        if (! $organizacion->pais || ! $organizacion->pais->activo) {
            throw new BusinessException(
                'No se puede reactivar la organización porque su país se encuentra inactivo.',
                409,
                'ORGANIZACION_PAIS_INACTIVO'
            );
        }

        $organizacion->update(['estado' => 'activo']);

        return $organizacion->fresh(self::RELACIONES);
    }
}
