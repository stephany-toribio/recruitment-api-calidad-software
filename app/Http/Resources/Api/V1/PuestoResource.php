<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Puesto
 */
class PuestoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'nombre'          => $this->nombre,
            'estado'          => $this->estado,
            'estado_catalogo' => $this->estado_catalogo,
            'activo'          => $this->estado === 'activo',
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
