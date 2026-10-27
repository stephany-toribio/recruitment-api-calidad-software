<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PerfilPostulanteResource extends JsonResource
{
    
    public function toArray(Request $request):array
    {
        $candidato =$this->candidato;
        $idsCandidato = $candidato->habilidades->pluck('id');
        $requisitos = $this->empleo->habilidades;

        $mustHave = $requisitos->where('pivot.tipo_requisito', 'must_have');
        $niceToHave = $requisitos ->where('pivot.tipo_requisito', 'nice_to_have');

        $resumen = fn ($h) => ['id' => $h->id, 'nombre' => $h->nombre];

        return [
            'postulacion' => 
            [
                'id'=>$this->id,
                'estado'=>['codigo'=>$this->estado->codigo(), 'nombre'=>$this->estado->nombre],
            ],
            // Sin dni, fecha_nacimiento ni genero
            'candidato' => [
                'nombre'              => $candidato->nombre,
                'apellidos'           => $candidato->apellidos,
                'correo'              => $candidato->usuario->correo,
                'celular'             => $candidato->celular,
                'busca_primer_empleo' => $candidato->busca_primer_empleo,
            ],
            'estudios'     => CandidatoEstudioResource::collection($candidato->estudios),
            'experiencias' => ExperienciaLaboralResource::collection($candidato->experiencias),
            'idiomas'      => CandidatoIdiomaResource::collection($candidato->idiomas),
            'habilidades' => [
                'tecnicas' => $candidato->habilidades->where('tipo_habilidad', 'tecnica')->map($resumen)->values(),
                'blandas'  => $candidato->habilidades->where('tipo_habilidad', 'blanda')->map($resumen)->values(),
            ],
            // Comparación por ID, sin porcentaje
            'requisitos_vacante' => [
                'must_have' => [
                    'posee'  => $mustHave->filter(fn ($h) => $idsCandidato->contains($h->id))->map($resumen)->values(),
                    'faltan' => $mustHave->reject(fn ($h) => $idsCandidato->contains($h->id))->map($resumen)->values(),
                ],
                'nice_to_have' => $niceToHave->map(fn ($h) => [
                    'id'     => $h->id,
                    'nombre' => $h->nombre,
                    'posee'  => $idsCandidato->contains($h->id),
                ])->values(),
            ],
        ];
    }       
}
