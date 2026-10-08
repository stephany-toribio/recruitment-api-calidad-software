<?php

namespace App\Http\Requests\Paises;

use Illuminate\Foundation\Http\FormRequest;

class CrearPaisRequest extends FormRequest
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
                'unique:paises,nombre',
            ],

            'activo' => [
                'prohibited',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del país es obligatorio.',
            'nombre.string' => 'El nombre del país debe ser una cadena de texto.',
            'nombre.max' => 'El nombre del país no puede superar los 100 caracteres.',
            'nombre.unique' => 'El país ya existe.',

            'activo.prohibited' => 'El estado no puede modificarse directamente.',
        ];
    }
}
