<?php

namespace App\Http\Requests\NivelesPuesto;

use Illuminate\Foundation\Http\FormRequest;

class FiltrarNivelesPuestoRequest extends FormRequest
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
