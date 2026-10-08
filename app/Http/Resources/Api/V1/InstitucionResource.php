<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Institucion
 */
class InstitucionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'nombre'     => $this->nombre,
            'pais_id'    => $this->pais_id,
            'pais'       => $this->whenLoaded('pais', fn () => [
                'id'     => $this->pais->id,
                'nombre' => $this->pais->nombre,
                'activo' => (bool) $this->pais->activo,
            ]),
            'estado'     => $this->estado,
            'activo'     => $this->estado === 'activo',
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
