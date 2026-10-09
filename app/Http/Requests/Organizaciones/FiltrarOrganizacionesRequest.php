<?php

namespace App\Http\Requests\Organizaciones;

use Illuminate\Foundation\Http\FormRequest;

class FiltrarOrganizacionesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo'    => ['sometimes', 'in:global,personalizado'],
            'estado'  => ['sometimes', 'in:activo,inactivo'],
            'pais_id' => ['sometimes', 'integer', 'exists:paises,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.in'         => 'El tipo debe ser global o personalizado.',
            'estado.in'       => 'El estado debe ser activo o inactivo.',
            'pais_id.integer' => 'El país seleccionado no es válido.',
            'pais_id.exists'  => 'El país seleccionado no existe.',
        ];
    }
}
