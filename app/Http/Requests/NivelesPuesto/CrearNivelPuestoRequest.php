<?php

namespace App\Http\Requests\NivelesPuesto;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

// El administrador solo crea niveles del catálogo global; los personalizados los escriben los candidatos
class CrearNivelPuestoRequest extends FormRequest
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
                // Único entre los globales. Si algún candidato ya lo escribió como personalizado, se permite
                Rule::unique('niveles_puesto', 'nombre')->whereNull('candidato_id'),
            ],

            // Posición en la jerarquía (1 = el más bajo). Si no se envía, va al final.
            'orden' => [
                'sometimes',
                'nullable',
                'integer',
                'min:1',
                'max:999',
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
            'nombre.required' => 'El nombre del nivel es obligatorio.',
            'nombre.string' => 'El nombre del nivel debe ser una cadena de texto.',
            'nombre.max' => 'El nombre del nivel no puede superar los 100 caracteres.',
            'nombre.unique' => 'El nivel ya existe en el catálogo.',

            'orden.integer' => 'El orden debe ser un número entero.',
            'orden.min' => 'El orden debe ser al menos 1.',
            'orden.max' => 'El orden no puede superar 999.',

            'candidato_id.prohibited' => 'El administrador solo puede crear niveles del catálogo global.',
            'estado.prohibited' => 'El estado no puede modificarse directamente.',
            'activo.prohibited' => 'El estado no puede modificarse directamente.',
        ];
    }
}
