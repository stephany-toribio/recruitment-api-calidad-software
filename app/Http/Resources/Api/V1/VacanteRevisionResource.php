<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Empleo;
use App\Models\RevisionVacante;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vacante vista por el administrador para revisarla.
 * El admin solo la consulta: la revisión no permite modificar su contenido.
 *
 * @mixin \App\Models\Empleo
 */
class VacanteRevisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'titulo'      => $this->titulo,
            'descripcion' => $this->descripcion,

            'nivel_experiencia' => [
                'codigo' => $this->nivel_experiencia,
                'nombre' => Empleo::NIVELES[$this->nivel_experiencia] ?? $this->nivel_experiencia,
            ],

            'salario_min'           => (float) $this->salario_min,
            'salario_max'           => (float) $this->salario_max,
            'fecha_inicio_vigencia' => $this->fecha_inicio_vigencia?->toDateString(),
            'fecha_fin_vigencia'    => $this->fecha_fin_vigencia?->toDateString(),
            'max_contrataciones'    => $this->numero_vacantes,

            'habilidades' => $this->whenLoaded('habilidades', fn () => $this->habilidades
                ->map(fn ($habilidad) => [
                    'id'             => $habilidad->id,
                    'nombre'         => $habilidad->nombre,
                    'tipo_habilidad' => $habilidad->tipo_habilidad,
                    'requisito'      => $habilidad->pivot->tipo_requisito,
                ])
                ->values()),

            'empresa' => $this->whenLoaded('empresa', fn () => [
                'id'           => $this->empresa->id,
                'razon_social' => $this->empresa->razon_social,
                'ruc'          => $this->empresa->ruc,
                'sector'       => $this->empresa->sector,
            ]),

            // Sin whenLoaded: una vacante pendiente no tiene fila y debe salir como estado 'pendiente', no como null
            'revision' => [
                'estado'      => RevisionVacante::estadoDe($this->revision),
                'motivo'      => $this->revision?->motivo,
                'revisado_en' => $this->revision?->created_at,
            ],

            'creado_en' => $this->created_at,
        ];
    }
}
