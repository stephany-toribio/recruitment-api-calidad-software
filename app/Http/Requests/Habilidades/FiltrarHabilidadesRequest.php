<?php

namespace App\Http\Requests\Habilidades;

use Illuminate\Foundation\Http\FormRequest;


class FiltrarHabilidadesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'areas'   => ['sometimes', 'array', 'max:5'],
            'areas.*' => ['integer', 'distinct', 'exists:areas_estudio,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'areas.array'      => 'Las áreas deben enviarse como lista (areas[]=1&areas[]=2).',
            'areas.max'        => 'No se pueden filtrar más de 5 áreas.',
            'areas.*.integer'  => 'Cada área debe ser un identificador numérico.',
            'areas.*.distinct' => 'No puede repetir áreas.',
            'areas.*.exists'   => 'Una o más áreas indicadas no existen.',
        ];
    }
}
