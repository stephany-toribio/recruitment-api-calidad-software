<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso API para transformar una Habilidad (técnica o blanda) del catálogo.
 */
class HabilidadResource extends JsonResource
{
    /**
     * Transforma el recurso en un array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'nombre'    => $this->nombre,
            'tipo'      => $this->tipo_habilidad ?? $this->tipo ?? 'tecnica',
        ];
    }
}
