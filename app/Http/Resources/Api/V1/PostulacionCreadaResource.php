<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use LogicException;

class PostulacionCreadaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (
            ! $this->resource->relationLoaded('estado')
            || ! $this->resource->relationLoaded('empleo')
        ) {
            throw new LogicException(
                'Falta cargar el estado o la vacante de la postulación.'
            );
        }

        $estado = $this->resource->estado;
        $vacante = $this->resource->empleo;

        if ($estado === null || $vacante === null) {
            throw new LogicException(
                'La postulación requiere un estado y una vacante.'
            );
        }

        return [
            'id' => (int) $this->resource->getKey(),

            'estado' => [
                'codigo' => $estado->codigo(),
                'nombre' => $estado->nombre,
            ],

            'retirada' => $this->resource->estaRetirada(),

            'fecha_postulacion' => $this->resource
                ->fecha_postulacion
                ->copy()
                ->utc()
                ->toIso8601String(),

            'vacante' => [
                'id' => (int) $vacante->getKey(),
                'titulo' => $vacante->titulo,
            ],
        ];
    }
}
