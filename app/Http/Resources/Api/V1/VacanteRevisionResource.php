<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Empleo;
use App\Models\RevisionVacante;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vacante consultada por el administrador para su revisión.
 *
 * @mixin Empleo
 */
class VacanteRevisionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'titulo' => $this->titulo,
            'descripcion' => $this->descripcion,
            'requisitos' => $this->requisitos ?? [],
            'beneficios' => $this->beneficios ?? [],

            'nivel_puesto' => $this->whenLoaded(
                'nivelPuesto',
                fn () => [
                    'id' => $this->nivel_puesto_id,
                    'nombre' => $this->nivelPuesto?->nombre,
                ]
            ),

            'area_profesional' => $this->whenLoaded(
                'areaEstudio',
                fn () => [
                    'id' => $this->area_estudio_id,
                    'nombre' => $this->areaEstudio?->nombre,
                ]
            ),

            'tipo_empleo' => $this->tipo_empleo,
            'ubicacion' => $this->ubicacion,

            'salario_min' => $this->salario_min === null
                ? null
                : (float) $this->salario_min,

            'salario_max' => $this->salario_max === null
                ? null
                : (float) $this->salario_max,

            'fecha_inicio_vigencia' => $this->fecha_inicio_vigencia
                ?->toDateString(),

            'fecha_fin_vigencia' => $this->fecha_fin_vigencia
                ?->toDateString(),

            'max_contrataciones' => $this->numero_vacantes,

            'habilidades' => $this->whenLoaded(
                'habilidades',
                fn () => $this->habilidades
                    ->map(fn ($habilidad) => [
                        'id' => $habilidad->id,
                        'nombre' => $habilidad->nombre,
                        'tipo_habilidad' => $habilidad->tipo_habilidad,
                        'requisito' => $habilidad->pivot->tipo_requisito,
                    ])
                    ->values()
            ),

            'empresa' => $this->whenLoaded(
                'empresa',
                fn () => [
                    'id' => $this->empresa->id,
                    'razon_social' => $this->empresa->razon_social,
                    'ruc' => $this->empresa->ruc,
                    'sector' => $this->empresa->sector,
                ]
            ),

            'revision' => [
                'estado' => RevisionVacante::estadoDe($this->revision),
                'motivo' => $this->revision?->motivo,
                'revisado_en' => $this->revision?->created_at,
            ],

            'creado_en' => $this->created_at,
        ];
    }
}
