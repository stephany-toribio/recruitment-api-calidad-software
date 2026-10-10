<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Idioma visto por el administrador (incluye los inactivos).
 *
 * @mixin \App\Models\Idioma
 */
class IdiomaAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'nombre'     => $this->nombre,
            'activo'     => (bool) $this->activo,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
