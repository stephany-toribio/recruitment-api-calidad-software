<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso API para transformar el modelo User a un contrato JSON estable.
 *
 * ¿Por qué usar Resources?
 * Evita devolver modelos Eloquent 'crudos' directamente a Angular o Python.
 * Protege campos sensibles (passwords, tokens internos) y garantiza que el contrato
 * no cambie aunque la base de datos sea modificada internamente.
 */
class UserResource extends JsonResource
{
    /**
     * Transforma el recurso en un array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'nombre'     => $this->name,
            'correo'     => $this->email,
            'creado_en'  => $this->created_at?->toISOString(),
            // En Sprint 1: agregar roles, datos de perfil candidato/empresa, etc.
        ];
    }
}
