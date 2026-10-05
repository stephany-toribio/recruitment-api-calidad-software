<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\CandidatoEstudio
 */
class CandidatoEstudioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'tipo_estudio' => $this->referencia('tipoEstudio'),
            'area_estudio' => $this->referencia('areaEstudio'),
            'pais'         => $this->referencia('pais'),
            'institucion'  => $this->referencia('institucion'),
            'carrera'      => $this->referencia('carrera'),
            'estado'       => $this->estado,
            'ciclo_actual' => $this->ciclo_actual,
            'fecha_inicio' => $this->fecha_inicio?->format('Y-m-d'),
            'fecha_fin'    => $this->fecha_fin?->format('Y-m-d'),
        ];
    }

    // {id, nombre} del catálogo relacionado, o null (institución y carrera son opcionales)
    private function referencia(string $relacion): mixed
    {
        return $this->whenLoaded($relacion, fn () => $this->{$relacion}
            ? ['id' => $this->{$relacion}->id, 'nombre' => $this->{$relacion}->nombre]
            : null);
    }
}
