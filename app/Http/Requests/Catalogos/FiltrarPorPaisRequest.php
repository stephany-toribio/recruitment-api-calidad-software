<?php

namespace App\Http\Requests\Catalogos;

use Illuminate\Foundation\Http\FormRequest;

// Filtro opcional ?pais_id= de los catálogos que dependen del país (instituciones, organizaciones)
class FiltrarPorPaisRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'pais_id' => ['sometimes', 'integer', 'exists:paises,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'pais_id.integer' => 'El país debe ser un identificador numérico.',
            'pais_id.exists'  => 'El país indicado no existe.',
        ];
    }
}
