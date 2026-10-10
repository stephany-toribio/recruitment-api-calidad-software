<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// Elemento de un catálogo para los selects del frontend: {id, nombre}, pais_id cuando el catálogo depende del país
// y personalizado cuando la consulta trae candidato_id (puestos del candidato autenticado)
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
            'personalizado' => $this->when(
                array_key_exists('candidato_id', $this->resource->getAttributes()),
                fn () => $this->candidato_id !== null
            ),
        ];
    }
}
