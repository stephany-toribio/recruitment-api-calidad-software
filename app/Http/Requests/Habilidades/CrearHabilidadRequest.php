<?php

namespace App\Http\Requests\Habilidades;

use Illuminate\Foundation\Http\FormRequest;

class CrearHabilidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:255',
                'unique:habilidades,nombre',
            ],
            'tipo_habilidad' => [
                'required',
                'string',
                'in:tecnica,blanda',
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
        ];
    }
}
