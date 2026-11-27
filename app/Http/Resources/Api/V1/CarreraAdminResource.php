<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Carrera vista por el administrador (incluye las inactivas y su tipo de estudio).
 *
 */
class CarreraAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'nombre'          => $this->nombre,
            'tipo_estudio_id' => $this->tipo_estudio_id,
            'tipo_estudio'    => $this->whenLoaded('tipoEstudio', fn () => [
                'id'     => $this->tipoEstudio->id,
                'nombre' => $this->tipoEstudio->nombre,
            ]),
            'activo'          => (bool) $this->activo,
            'created_at'      => $this->created_at,
            'updated_at'      => $this->updated_at,
        ];
    }
}
