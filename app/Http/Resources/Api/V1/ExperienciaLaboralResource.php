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
            // personalizado: lo escribió el candidato. activo = false: el administrador lo deshabilitó (lo conserva, pero no puede volver a elegirlo)
            'puesto'       => $this->whenLoaded('puesto', fn () => [
                'id'            => $this->puesto->id,
                'nombre'        => $this->puesto->nombre,
                'personalizado' => $this->puesto->esPersonalizado(),
                'activo'        => $this->puesto->estado === 'activo',
            ]),
            // estado_catalogo permite al frontend mostrar "pendiente de validación" en lo que propuso el candidato
            'organizacion' => $this->whenLoaded('organizacion', fn () => [
                'id'              => $this->organizacion->id,
                'nombre'          => $this->organizacion->nombre,
                'estado_catalogo' => $this->organizacion->estado_catalogo,
            ]),
            'pais'         => $this->whenLoaded('pais', fn () => [
                'id'     => $this->pais->id,
                'nombre' => $this->pais->nombre,
            ]),
            'nivel'        => $this->nivel,
            'fecha_inicio' => $this->fecha_inicio?->format('Y-m-d'),
            'fecha_fin'    => $this->fecha_fin?->format('Y-m-d'),
            'descripcion'  => $this->descripcion,
        ];
    }
}
