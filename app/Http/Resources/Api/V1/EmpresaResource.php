<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Empresa
 */
class EmpresaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'correo' => $this->whenLoaded(
                'usuario',
                fn () => $this->usuario->correo
            ),
            'encargado_empresa' => $this->encargado_empresa,
            'razon_social' => $this->razon_social,
            'sector' => $this->sector,
            'ruc' => $this->ruc,
            'sitio_web' => $this->sitio_web,
            'descripcion' => $this->descripcion,
        ];
    }
}
