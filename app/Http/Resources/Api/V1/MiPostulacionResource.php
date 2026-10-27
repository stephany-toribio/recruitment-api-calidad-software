<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MiPostulacionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                => $this->id,
            'estado'            => ['codigo' => $this->estado->codigo(), 'nombre' => $this->estado->nombre],
            'retirada'          => $this->estaRetirada(),
            'retirada_en'       => $this->retirada_en,
            'puede_retirar'     => $this->puedeRetirarse(),
            'fecha_postulacion' => $this->fecha_postulacion,
            'vacante' => [
                'id'      => $this->empleo->id,
                'titulo'  => $this->empleo->titulo,
                'empresa' => ['razon_social' => $this->empleo->empresa->razon_social],
                // Estado de la vacante, distinto del de la postulación: activa, pausada, finalizada o eliminada
                'estado'  => $this->empleo->estadoDisponibilidad(),
                'vigente' => $this->empleo->estaVigente(),
            ],
            // disponible → GET /vacantes/{vacante.id} · historico → GET /candidatos/me/postulaciones/{id}/vacante · null → sin detalle
            'ver_vacante'       => $this->ver_vacante,
        ];
    }
}
