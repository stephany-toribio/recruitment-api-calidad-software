<?php

namespace App\Http\Requests\Puestos;

use Illuminate\Foundation\Http\FormRequest;

class FiltrarPuestosRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo'   => ['sometimes', 'in:global,personalizado'],
            'estado' => ['sometimes', 'in:activo,inactivo'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo.in'   => 'El tipo debe ser global o personalizado.',
            'estado.in' => 'El estado debe ser activo o inactivo.',
        ];
    }
}
