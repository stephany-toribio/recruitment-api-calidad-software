<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Empleo;
use App\Models\PagoVacante;
use App\Models\RevisionVacante;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vacante vista por su empresa.
 *
 * Relaciones: nivelPuesto, areaEstudio, habilidades, revision y pagos.
 *
 * @mixin Empleo
 */
class VacanteEmpresaResource extends JsonResource
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

            // Conservar NULL: ausencia de salario no equivale a cero.
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

            'estado' => [
                'general' => $this->estadoGeneral(),
                'revision' => RevisionVacante::estadoDe($this->revision),
                'motivo_no_aprobacion' => $this->revision?->decision === RevisionVacante::NO_APROBADA
                    ? $this->revision->motivo
                    : null,
                'pago' => PagoVacante::estadoDe($this->pagos),
                'disponibilidad' => $this->estadoDisponibilidad(),
                'vigente' => $this->estaVigente(),
                'publicada_en' => $this->publicadaEn(),
            ],

            'postulaciones_count' => $this->whenCounted(
                'postulaciones',
                fn ($total) => (int) $total
            ),

            'postulaciones_semana_count' => $this->whenCounted(
                'postulaciones_semana',
                fn ($total) => (int) $total
            ),

            'creado_en' => $this->created_at,
            'eliminado_en' => $this->deleted_at,
        ];
    }
}
