<?php

namespace App\Http\Requests\Habilidades;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarHabilidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('habilidades', 'nombre')
                    ->ignore($this->route('habilidad')),
            ],

            'tipo_habilidad' => [
                'sometimes',
                'required',
                'string',
                'in:tecnica,blanda',
            ],

            'estado' => [
                'sometimes',
                'required',
                'string',
                'in:activo,inactivo',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la habilidad es obligatorio.',
            'nombre.string' => 'El nombre de la habilidad debe ser texto.',
            'nombre.max' => 'El nombre de la habilidad no puede superar los 255 caracteres.',
            'nombre.unique' => 'La habilidad ya existe.',

            'tipo_habilidad.required' => 'El tipo de habilidad es obligatorio.',
            'tipo_habilidad.string' => 'El tipo de habilidad debe ser texto.',
            'tipo_habilidad.in' => 'El tipo de habilidad debe ser tecnica o blanda.',

            'estado.required' => 'El estado es obligatorio.',
            'estado.string' => 'El estado debe ser texto.',
            'estado.in' => 'El estado debe ser activo o inactivo.',
        ];
    }
}
