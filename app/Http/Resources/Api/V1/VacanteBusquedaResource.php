<?php

namespace App\Http\Resources\Api\V1;

use App\Services\Vacantes\AproximacionPostulantes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use LogicException;

class VacanteBusquedaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        if (! array_key_exists(
            'postulaciones_count',
            $this->resource->getAttributes()
        )) {
            throw new LogicException(
                'La búsqueda debe cargar el conteo de postulaciones.'
            );
        }

        foreach ([
            'empresa',
            'nivelPuesto',
            'areaEstudio',
            'habilidades',
        ] as $relacion) {
            if (! $this->resource->relationLoaded($relacion)) {
                throw new LogicException(
                    "La búsqueda debe cargar la relación {$relacion}."
                );
            }
        }

        $publicadaEn = $this->resource->getAttribute('publicada_en');

        if ($publicadaEn === null) {
            throw new LogicException(
                'La búsqueda debe proporcionar la fecha de publicación.'
            );
        }

        return [
            'id' => (int) $this->id,
            'titulo' => $this->titulo,

            'empresa' => [
                'razon_social' => $this->empresa->razon_social,
                'sector' => $this->empresa->sector,
            ],

            'nivel_puesto' => [
                'id' => (int) $this->nivelPuesto->id,
                'nombre' => $this->nivelPuesto->nombre,
            ],

            'area_profesional' => [
                'id' => (int) $this->areaEstudio->id,
                'nombre' => $this->areaEstudio->nombre,
            ],

            'tipo_empleo' => $this->tipo_empleo,
            'ubicacion' => $this->ubicacion,

            'salario_min' => $this->salario_min === null
                ? null
                : (float) $this->salario_min,

            'salario_max' => $this->salario_max === null
                ? null
                : (float) $this->salario_max,

            'fecha_inicio_vigencia' => $this->fecha_inicio_vigencia
                ->toDateString(),

            'fecha_fin_vigencia' => $this->fecha_fin_vigencia
                ->toDateString(),

            'publicada_en' => Carbon::parse(
                $publicadaEn,
                config('app.timezone', 'UTC')
            )->utc()->toIso8601String(),

            'habilidades' => $this->habilidades
                ->map(fn ($habilidad): array => [
                    'id' => (int) $habilidad->id,
                    'nombre' => $habilidad->nombre,
                    'tipo_habilidad' => $habilidad->tipo_habilidad,
                    'requisito' => $habilidad->pivot->tipo_requisito,
                ])
                ->values()
                ->all(),

            'postulantes_aprox' => AproximacionPostulantes::texto(
                (int) $this->postulaciones_count
            ),
        ];
    }
}
