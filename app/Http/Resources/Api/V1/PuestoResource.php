<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Puesto visto por el administrador (moderación).
 *
 * @mixin \App\Models\Puesto
 */
class PuestoResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'nombre'        => $this->nombre,
            'personalizado' => $this->esPersonalizado(),
            'creado_por'    => $this->whenLoaded('creador', fn () => $this->creador ? [
                'candidato_id' => $this->creador->id,
                'nombre'       => $this->creador->nombre,
                'apellidos'    => $this->creador->apellidos,
            ] : null),
            // Cuántos candidatos escribieron ese mismo nombre como personalizado (ayuda a decidir si crearlo como global)
            'candidatos_con_mismo_nombre' => $this->when(
                isset($this->candidatos_con_mismo_nombre),
                fn () => (int) $this->candidatos_con_mismo_nombre
            ),
            'estado'        => $this->estado,
            'activo'        => $this->estado === 'activo',
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,
        ];
    }
}
