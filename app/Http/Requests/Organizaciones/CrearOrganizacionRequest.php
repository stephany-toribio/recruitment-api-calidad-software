<?php

namespace App\Http\Requests\Organizaciones;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// El administrador solo crea organizaciones del catálogo global; las personalizadas las escriben los candidatos
class CrearOrganizacionRequest extends FormRequest
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
                // Única entre las globales del país. Si algún candidato ya la escribió como personalizada, se permite:
                // su valor sigue siendo suyo y desde ahora quien la escriba en ese país usará la global.
                Rule::unique('organizaciones', 'nombre')
                    ->where('pais_id', $this->input('pais_id'))
                    ->whereNull('candidato_id'),
            ],

            'pais_id' => [
                'required',
                'integer',
                Rule::exists('paises', 'id')->where('activo', true),
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
            'nombre.required' => 'El nombre de la organización es obligatorio.',
            'nombre.string' => 'El nombre de la organización debe ser una cadena de texto.',
            'nombre.max' => 'El nombre de la organización no puede superar los 200 caracteres.',
            'nombre.unique' => 'La organización ya existe en el catálogo de ese país.',

            'pais_id.required' => 'El país es obligatorio.',
            'pais_id.integer' => 'El país seleccionado no es válido.',
            'pais_id.exists' => 'El país no existe o está inactivo.',

            'candidato_id.prohibited' => 'El administrador solo puede crear organizaciones del catálogo global.',
            'estado.prohibited' => 'El estado no puede modificarse directamente.',
            'activo.prohibited' => 'El estado no puede modificarse directamente.',
        ];
    }
}
