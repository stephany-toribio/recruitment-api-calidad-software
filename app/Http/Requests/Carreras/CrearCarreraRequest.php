<?php

namespace App\Http\Requests\Carreras;

use App\Models\TipoEstudio;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CrearCarreraRequest extends FormRequest
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
                // Mismo nombre permitido en tipos distintos (unique nombre + tipo_estudio_id)
                Rule::unique('carreras', 'nombre')
                    ->where('tipo_estudio_id', $this->input('tipo_estudio_id')),
            ],

            // Solo los tipos que llevan carrera (Universitario, Posgrado, Master, Doctorado)
            'tipo_estudio_id' => [
                'required',
                'integer',
                Rule::exists('tipos_estudio', 'id')->whereIn('nombre', TipoEstudio::CON_CARRERA),
            ],

            'activo' => [
                'prohibited',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la carrera es obligatorio.',
            'nombre.string' => 'El nombre de la carrera debe ser una cadena de texto.',
            'nombre.max' => 'El nombre de la carrera no puede superar los 255 caracteres.',
            'nombre.unique' => 'La carrera ya existe para ese tipo de estudio.',

            'tipo_estudio_id.required' => 'El tipo de estudio es obligatorio.',
            'tipo_estudio_id.integer' => 'El tipo de estudio no es válido.',
            'tipo_estudio_id.exists' => 'El tipo de estudio no existe o no lleva carrera.',

            'activo.prohibited' => 'El estado no puede modificarse directamente.',
        ];
    }
}
