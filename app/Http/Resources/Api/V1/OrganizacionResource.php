<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Organización vista por el administrador (moderación).
 *
 * @mixin \App\Models\Organizacion
 */
class OrganizacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'nombre'        => $this->nombre,
            'pais_id'       => $this->pais_id,
            'pais'          => $this->whenLoaded('pais', fn () => [
                'id'     => $this->pais->id,
                'nombre' => $this->pais->nombre,
                'activo' => (bool) $this->pais->activo,
            ]),
            'personalizado' => $this->esPersonalizada(),
            'creado_por'    => $this->whenLoaded('creador', fn () => $this->creador ? [
                'candidato_id' => $this->creador->id,
                'nombre'       => $this->creador->nombre,
                'apellidos'    => $this->creador->apellidos,
            ] : null),
            // Cuántos candidatos escribieron ese mismo nombre en ese país como personalizada
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
