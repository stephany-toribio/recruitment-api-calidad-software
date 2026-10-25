<?php

namespace App\Http\Requests\Postulaciones;

use App\Models\MotivoRechazo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RechazarPostulacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'motivo_rechazo_id' => [
                'bail',
                'required',
                'integer',
                Rule::exists('motivos_rechazo', 'id')
                    ->whereIn('motivo', MotivoRechazo::PERMITIDOS),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo_rechazo_id.required' => 'Seleccione un motivo de rechazo.',
            'motivo_rechazo_id.integer' => 'El motivo de rechazo no es válido.',
            'motivo_rechazo_id.exists' => 'El motivo de rechazo no es válido.',
        ];
    }
}