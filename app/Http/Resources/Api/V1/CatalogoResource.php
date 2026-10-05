<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Elemento de un catálogo para los selects del frontend: {id, nombre} y pais_id cuando el catálogo depende del país
class CatalogoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'      => $this->id,
            'nombre'  => $this->nombre,
            'pais_id' => $this->when(
                array_key_exists('pais_id', $this->resource->getAttributes()),
                fn () => $this->pais_id
            ),
        ];
    }
}
