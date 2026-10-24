<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Un intento de pago simulado (PAG-01 a PAG-03). De la tarjeta solo se muestran los últimos 4 dígitos.
 *
 * @mixin \App\Models\PagoVacante
 */
class PagoVacanteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'resultado' => $this->resultado,
            'monto'     => (float) $this->monto,
            'tarjeta'   => $this->tarjeta_ultimos4 !== null ? '**** ' . $this->tarjeta_ultimos4 : null,
            'fecha'     => $this->created_at,
        ];
    }
}
