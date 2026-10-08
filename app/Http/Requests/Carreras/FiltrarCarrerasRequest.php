<?php

namespace App\Http\Requests\Carreras;

use Illuminate\Foundation\Http\FormRequest;

// Filtro opcional ?tipo_estudio_id= del catálogo de carreras (las carreras son globales)
class FiltrarCarrerasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_estudio_id' => ['sometimes', 'integer', 'exists:tipos_estudio,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_estudio_id.integer' => 'El tipo de estudio debe ser un identificador numérico.',
            'tipo_estudio_id.exists'  => 'El tipo de estudio indicado no existe.',
        ];
    }
}
