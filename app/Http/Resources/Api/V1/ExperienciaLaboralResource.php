<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\ExperienciaLaboral
 */
class ExperienciaLaboralResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            // personalizado: lo escribió el candidato. activo = false el administrador lo deshabilitó 
            'puesto'       => $this->whenLoaded('puesto', fn () => [
                'id'            => $this->puesto->id,
                'nombre'        => $this->puesto->nombre,
                'personalizado' => $this->puesto->esPersonalizado(),
                'activo'        => $this->puesto->estado === 'activo',
            ]),
            // Mismo criterio que el puesto
            'organizacion' => $this->whenLoaded('organizacion', fn () => [
                'id'            => $this->organizacion->id,
                'nombre'        => $this->organizacion->nombre,
                'personalizado' => $this->organizacion->esPersonalizada(),
                'activo'        => $this->organizacion->estado === 'activo',
            ]),
            'pais'         => $this->whenLoaded('pais', fn () => [
                'id'     => $this->pais->id,
                'nombre' => $this->pais->nombre,
            ]),
            // Mismo criterio que el puesto (antes era un código fijo como "junior")
            'nivel'        => $this->whenLoaded('nivelPuesto', fn () => [
                'id'            => $this->nivelPuesto->id,
                'nombre'        => $this->nivelPuesto->nombre,
                'personalizado' => $this->nivelPuesto->esPersonalizado(),
                'activo'        => $this->nivelPuesto->estado === 'activo',
            ]),
            'fecha_inicio' => $this->fecha_inicio?->format('Y-m-d'),
            'fecha_fin'    => $this->fecha_fin?->format('Y-m-d'),
            'descripcion'  => $this->descripcion,
        ];
    }
}
