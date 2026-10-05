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
            'apellidos'        => $this->apellidos,
            'dni'              => $this->dni,
            'fecha_nacimiento' => $this->fecha_nacimiento instanceof \DateTimeInterface 
                                    ? $this->fecha_nacimiento->format('Y-m-d') 
                                    : $this->fecha_nacimiento,
            'celular'          => $this->celular,
            'genero'           => $this->genero,
            'busca_primer_empleo' => $this->busca_primer_empleo,
            'estudios'         => CandidatoEstudioResource::collection($this->whenLoaded('estudios')),
            'idiomas'          => CandidatoIdiomaResource::collection($this->whenLoaded('idiomas')),
            'experiencias'     => ExperienciaLaboralResource::collection($this->whenLoaded('experiencias')),
            'habilidades'      => HabilidadResource::collection($this->whenLoaded('habilidades')),
            'creado_en'        => $this->created_at?->toISOString(),
        ];
    }
}