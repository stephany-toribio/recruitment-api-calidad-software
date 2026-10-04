<?php

namespace App\Services\Admin;

use App\Exceptions\BusinessException;
use App\Models\Usuario;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UsuarioAdminService
{
    public function listar(): Collection
    {
        return Usuario::with(['rol', 'candidato', 'empresa'])
            ->orderBy('id')
            ->get();
    }

    public function suspender(Usuario $usuario, ?string $motivo = null): Usuario
    {
        $usuario->loadMissing('rol');

        if (!in_array($usuario->rol?->nombre, ['candidato', 'empresa'], true)) {
            throw new BusinessException(
                'Solo se pueden suspender cuentas de candidatos o empresas.',
                422,
                'USER_SUSPENSION_NOT_ALLOWED'
            );
        }

        return DB::transaction(function () use ($usuario, $motivo) {
            $usuario->update([
                'estado_cuenta' => 'suspendido',
                'motivo_suspension' => $motivo,
                'fecha_suspension' => now(),
            ]);

            $usuario->tokens()->delete();

            return $usuario->fresh(['rol', 'candidato', 'empresa']);
        });
    }

    public function reactivar(Usuario $usuario): Usuario
    {
        $usuario->loadMissing('rol');

        if (!in_array($usuario->rol?->nombre, ['candidato', 'empresa'], true)) {
            throw new BusinessException(
                'Solo se pueden reactivar cuentas de candidatos o empresas.',
                422,
                'USER_REACTIVATION_NOT_ALLOWED'
            );
        }

        $usuario->update([
            'estado_cuenta' => 'activo',
            'motivo_suspension' => null,
            'fecha_suspension' => null,
        ]);

        return $usuario->fresh(['rol', 'candidato', 'empresa']);
    }
}