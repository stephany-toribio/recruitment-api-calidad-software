<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\TipoEstudio
 */
class TipoEstudioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'nombre'           => $this->nombre,
            'requiere_carrera' => $this->requiereCarrera(),
            // true: solo se pide el área de estudio (sin país, institución, carrera, estado ni fechas)
            'solo_area'        => $this->soloArea(),
        ];
    }
}
