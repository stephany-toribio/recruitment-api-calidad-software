<?php

namespace App\Services\Admin;

use App\Exceptions\BusinessException;
use App\Models\Puesto;
use Illuminate\Database\Eloquent\Collection;

// El administrador solo habilita o deshabilita puestos; los puestos nuevos los proponen los candidatos al registrarse.
class PuestoAdminService
{
    public function listar(): Collection
    {
        return Puesto::query()
            ->orderBy('nombre')
            ->get();
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

        return $puesto->fresh();
    }

    /**
     * Activar un puesto propuesto por un candidato (pendiente) también lo aprueba:
     * la tabla no admite un puesto activo que siga pendiente (chk_puestos_activo_solo_aprobado).
     */
    public function reactivar(Puesto $puesto): Puesto
    {
        if ($puesto->estado === 'activo') {
            throw new BusinessException(
                'El puesto ya se encuentra activo.',
                409,
                'PUESTO_ALREADY_ACTIVE'
            );
        }

        $puesto->update([
            'estado'          => 'activo',
            'estado_catalogo' => 'aprobado',
        ]);

        return $puesto->fresh();
    }
}
