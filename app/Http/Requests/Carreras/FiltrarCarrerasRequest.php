<?php

namespace App\Http\Requests\Carreras;

use Illuminate\Foundation\Http\FormRequest;

// Filtros opcionales ?tipo_estudio_id=&pais_id= del catálogo de carreras
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
            'pais_id'         => ['sometimes', 'integer', 'exists:paises,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_estudio_id.integer' => 'El tipo de estudio debe ser un identificador numérico.',
            'tipo_estudio_id.exists'  => 'El tipo de estudio indicado no existe.',
            'pais_id.integer'         => 'El país debe ser un identificador numérico.',
            'pais_id.exists'          => 'El país indicado no existe.',
        ];
    }
}
