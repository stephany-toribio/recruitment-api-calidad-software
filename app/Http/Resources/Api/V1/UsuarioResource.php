<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'correo' => $this->correo,
            'rol' => $this->rol?->nombre,
            'estado_cuenta' => $this->estado_cuenta,
            'creado_en' => $this->created_at?->toISOString(),
        ];
    }
}