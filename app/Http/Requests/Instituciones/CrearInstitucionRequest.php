<?php

namespace App\Http\Requests\Instituciones;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrearInstitucionRequest extends FormRequest
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
                'max:255',
                // Mismo nombre permitido en países distintos (unique nombre + pais_id).
                Rule::unique('instituciones', 'nombre')
                    ->where('pais_id', $this->input('pais_id')),
            ],

            'pais_id' => [
                'required',
                'integer',
                Rule::exists('paises', 'id')->where('activo', true),
            ],

            'estado' => [
                'prohibited',
            ],

            'activo' => [
                'prohibited',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la institución es obligatorio.',
            'nombre.string' => 'El nombre de la institución debe ser una cadena de texto.',
            'nombre.max' => 'El nombre de la institución no puede superar los 255 caracteres.',
            'nombre.unique' => 'La institución ya existe en ese país.',

            'pais_id.required' => 'El país es obligatorio.',
            'pais_id.integer' => 'El país seleccionado no es válido.',
            'pais_id.exists' => 'El país no existe o está inactivo.',

            'estado.prohibited' => 'El estado no puede modificarse directamente.',
            'activo.prohibited' => 'El estado no puede modificarse directamente.',
        ];
    }
}
