<?php

namespace App\Http\Requests\AreasEstudio;

use Illuminate\Foundation\Http\FormRequest;

class CrearAreaEstudioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('nombre') && is_string($this->nombre)) {
            $this->merge([
                'nombre' => preg_replace('/\s+/u', ' ', trim($this->nombre)),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'nombre' => [
                'required',
                'string',
                'max:100',
                'unique:areas_estudio,nombre',
            ],

            'activo' => [
                'prohibited',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del área de estudio es obligatorio.',
            'nombre.string' => 'El nombre del área de estudio debe ser una cadena de texto.',
            'nombre.max' => 'El nombre del área de estudio no puede superar los 100 caracteres.',
            'nombre.unique' => 'El área de estudio ya existe.',

            'activo.prohibited' => 'El estado no puede modificarse directamente.',
        ];
    }
}
