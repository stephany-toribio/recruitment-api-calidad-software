<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso API para transformar una Postulación laboral.
 */
class PostulacionResource extends JsonResource
{
    /**
     * Transforma el recurso en un array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'estado'            => $this->estado ?? 'pendiente', // 'pendiente', 'en_revision', 'rechazado', 'seleccionado'
            'fecha_postulacion' => $this->created_at?->toISOString(),

            // Relaciones condicionales:
            'vacante'           => new VacanteResource($this->whenLoaded('vacante')),
            'candidato'         => new CandidatoResource($this->whenLoaded('candidato')),
        ];
    }
}
