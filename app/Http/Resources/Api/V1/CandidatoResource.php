<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidatoResource extends JsonResource
{
    /**
     * Transforma el recurso en un array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'usuario_id'       => $this->usuario_id,
            'correo'           => $this->usuario?->correo,
            'nombre'           => $this->nombre,
            'apellido_paterno' => $this->apellido_paterno,
            'apellido_materno' => $this->apellido_materno,
            'dni'              => $this->dni,
            'fecha_nacimiento' => $this->fecha_nacimiento instanceof \DateTimeInterface 
                                    ? $this->fecha_nacimiento->format('Y-m-d') 
                                    : $this->fecha_nacimiento,
            'celular'          => $this->celular,
            'genero'           => $this->genero,
            'disponibilidad'   => $this->disponibilidad,
            'carreras'         => $this->whenLoaded('carreras', fn() => $this->carreras->map(fn($c) => [
                'id'           => $c->id,
                'carrera_id'   => $c->carrera_id,
                'area'         => $c->area,
                'institucion'  => $c->institucion,
                'estado'       => $c->estado,
                'ciclo_actual' => $c->ciclo_actual,
                'fecha_inicio' => $c->fecha_inicio?->format('Y-m-d'),
                'fecha_fin'    => $c->fecha_fin?->format('Y-m-d'),
            ])),
            'habilidades'      => HabilidadResource::collection($this->whenLoaded('habilidades')),
            'creado_en'        => $this->created_at?->toISOString(),
        ];
    }
}