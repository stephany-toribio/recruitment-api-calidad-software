<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Recurso API para transformar una Vacante hacia Angular o el microservicio de Python.
 *
 * Contrato JSON estable para búsquedas y cálculos de compatibilidad/match de IA.
 */
class VacanteResource extends JsonResource
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
            'titulo'       => $this->titulo,
            'descripcion'  => $this->descripcion,
            'modalidad'    => $this->modalidad,
            'salario_min'  => $this->salario_min,
            'salario_max'  => $this->salario_max,
            'fecha_cierre' => $this->fecha_cierre,
            // Ejemplo de relación cargada condicionalmente:
            // 'empresa'     => new EmpresaResource($this->whenLoaded('empresa')),
            // 'habilidades' => HabilidadResource::collection($this->whenLoaded('habilidades')),
        ];
    }
}
