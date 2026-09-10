<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso API para transformar los datos de una Empresa.
 */
class EmpresaResource extends JsonResource
{
    /**
     * Transforma el recurso en un array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'razon_social' => $this->razon_social,
            'ruc'          => $this->ruc,
            'sector'       => $this->sector,
            'sitio_web'    => $this->sitio_web,
            'descripcion'  => $this->descripcion,
            // En Sprint 1: relaciones como vacantes activas cuando se carguen con with('vacantes')
        ];
    }
}
