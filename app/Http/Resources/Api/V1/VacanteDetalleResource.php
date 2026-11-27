<?php

namespace App\Http\Resources\Api\V1;

use App\Services\Vacantes\AproximacionPostulantes;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use LogicException;

class VacanteDetalleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $contexto = $this->resource;
        $vacante = $contexto['vacante'];

        foreach ([
            'empresa',
            'nivelPuesto',
            'areaEstudio',
            'habilidades',
        ] as $relacion) {
            if (! $vacante->relationLoaded($relacion)) {
                throw new LogicException(
                    "Falta cargar la relación {$relacion} del detalle."
                );
            }
        }

        $atributos = $vacante->getAttributes();

        if (
            ! array_key_exists('postulaciones_count', $atributos)
            || ! array_key_exists('publicada_en', $atributos)
        ) {
            throw new LogicException(
                'Falta cargar la publicación o el conteo de postulaciones.'
            );
        }

        $empresa = $vacante->empresa;
        $nivel = $vacante->nivelPuesto;
        $area = $vacante->areaEstudio;

        if ($empresa === null || $nivel === null || $area === null) {
            throw new LogicException(
                'El detalle requiere empresa, nivel del puesto y área profesional.'
            );
        }

        $descripcionEmpresa = trim(
            (string) $empresa->descripcion
        );

        $publicadaEn = $vacante->getAttribute('publicada_en');
        $postulacion = $contexto['postulacion'];

        return [
            'id' => (int) $vacante->getKey(),
            'titulo' => $vacante->titulo,
            'descripcion' => $vacante->descripcion,
            'requisitos' => $vacante->requisitos ?? [],
            'beneficios' => $vacante->beneficios ?? [],

            'empresa' => [
                'razon_social' => $empresa->razon_social,
                'sector' => $empresa->sector,
                'descripcion' => $descripcionEmpresa === ''
                    ? null
                    : $descripcionEmpresa,
            ],

            'nivel_puesto' => [
                'id' => (int) $nivel->getKey(),
                'nombre' => $nivel->nombre,
            ],

            'area_profesional' => [
                'id' => (int) $area->getKey(),
                'nombre' => $area->nombre,
            ],

            'tipo_empleo' => $vacante->tipo_empleo,
            'ubicacion' => $vacante->ubicacion,

            'salario_min' => $vacante->salario_min === null
                ? null
                : (float) $vacante->salario_min,

            'salario_max' => $vacante->salario_max === null
                ? null
                : (float) $vacante->salario_max,

            'fecha_inicio_vigencia' => $vacante
                ->fecha_inicio_vigencia
                ->toDateString(),

            'fecha_fin_vigencia' => $vacante
                ->fecha_fin_vigencia
                ->toDateString(),

            'publicada_en' => $publicadaEn === null
                ? null
                : Carbon::parse(
                    $publicadaEn,
                    config('app.timezone')
                )->utc()->toIso8601String(),

            'habilidades' => $vacante->habilidades
                ->map(fn ($habilidad): array => [
                    'id' => (int) $habilidad->getKey(),
                    'nombre' => $habilidad->nombre,
                ])
                ->values()
                ->all(),

            'postulantes_aprox' => AproximacionPostulantes::texto(
                (int) $vacante->postulaciones_count
            ),

            'modo_consulta' => $contexto['modo_consulta'],

            // Etiqueta para el front: activa, pausada, finalizada o eliminada
            'estado' => $vacante->estadoDisponibilidad(),

            'postulacion' => $postulacion === null
                ? null
                : [
                    'id' => (int) $postulacion->getKey(),
                    'retirada' => $postulacion->estaRetirada(),
                ],

            'puede_postular' => $contexto['puede_postular'],
            'motivo_no_postulable' => $contexto['motivo_no_postulable'],
        ];
    }
}
