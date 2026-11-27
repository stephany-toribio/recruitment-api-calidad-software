<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class PostulacionRetiradaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (! $this->resource->relationLoaded('estado')) {
            throw new LogicException(
                'Falta cargar el estado de la postulación.'
            );
        }

        $estado = $this->resource->estado;

        if (
            $estado === null
            || $this->resource->retirada_en === null
        ) {
            throw new LogicException(
                'La respuesta requiere una postulación retirada y su estado.'
            );
        }

        return [
            'id' => (int) $this->resource->getKey(),

            'estado' => [
                'codigo' => $estado->codigo(),
                'nombre' => $estado->nombre,
            ],

            'retirada' => true,

            'retirada_en' => $this->resource
                ->retirada_en
                ->copy()
                ->utc()
                ->toIso8601String(),
        ];
    }
}
