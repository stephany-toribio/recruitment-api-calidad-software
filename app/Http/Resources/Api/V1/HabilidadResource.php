<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HabilidadResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'tipo_habilidad' => $this->tipo_habilidad,
            'activo' => (bool) $this->activo,

            'area_estudio' => $this->area_estudio_id
                ? [
                    'id' => $this->areaEstudio?->id,
                    'nombre' => $this->areaEstudio?->nombre,
                    'activo' => $this->areaEstudio !== null
                        ? (bool) $this->areaEstudio->activo
                        : null,
                ]
                : null,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
