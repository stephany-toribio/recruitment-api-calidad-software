<?php

namespace App\Http\Resources\Api\V1;

use App\Models\Empleo;
use App\Models\PagoVacante;
use App\Models\RevisionVacante;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Vacante vista por su empresa.
 * Necesita cargadas las relaciones habilidades, revision y pagos.
 *
 * @mixin \App\Models\Empleo
 */
class VacanteEmpresaResource extends JsonResource
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

            // El peso numérico no se expone: es interno para el match de la Demo 4
            'habilidades' => $this->whenLoaded('habilidades', fn () => $this->habilidades
                ->map(fn ($habilidad) => [
                    'id'             => $habilidad->id,
                    'nombre'         => $habilidad->nombre,
                    'tipo_habilidad' => $habilidad->tipo_habilidad,
                    'requisito'      => $habilidad->pivot->tipo_requisito,
                ])
                ->values()),

            'estado' => [
                'general'        => $this->estadoGeneral(),
                'revision'       => RevisionVacante::estadoDe($this->revision),
                // La empresa ve por qué no se aprobó su vacante; null en cualquier otro estado de revisión
                'motivo_no_aprobacion' => $this->revision?->decision === RevisionVacante::NO_APROBADA
                    ? $this->revision->motivo
                    : null,
                'pago'           => PagoVacante::estadoDe($this->pagos),
                'disponibilidad' => $this->estadoDisponibilidad(),
                'vigente'        => $this->estaVigente(),
                'publicada_en'   => $this->publicadaEn(),
            ],

            'creado_en'    => $this->created_at,
            'eliminado_en' => $this->deleted_at,
        ];
    }
}
