<?php

namespace App\Http\Requests\Puestos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// El administrador solo crea puestos del catálogo global; los personalizados los escriben los candidatos
class CrearPuestoRequest extends FormRequest
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
                'max:200',
                // Único entre los globales. Si algún candidato ya lo escribió como personalizado, se permite:
                // su valor sigue siendo suyo y desde ahora quien lo escriba usará el global.
                Rule::unique('puestos', 'nombre')->whereNull('candidato_id'),
            ],

            'candidato_id' => [
                'prohibited',
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
            'nombre.required' => 'El nombre del puesto es obligatorio.',
            'nombre.string' => 'El nombre del puesto debe ser una cadena de texto.',
            'nombre.max' => 'El nombre del puesto no puede superar los 200 caracteres.',
            'nombre.unique' => 'El puesto ya existe en el catálogo.',

            'candidato_id.prohibited' => 'El administrador solo puede crear puestos del catálogo global.',
            'estado.prohibited' => 'El estado no puede modificarse directamente.',
            'activo.prohibited' => 'El estado no puede modificarse directamente.',
        ];
    }
}
