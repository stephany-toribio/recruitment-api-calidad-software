<?php

namespace App\Http\Requests\Instituciones;

use App\Models\Institucion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarInstitucionRequest extends FormRequest
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
        $institucion = $this->route('institucion');

        $institucionId = $institucion instanceof Institucion
            ? $institucion->id
            : $institucion;

        $paisId = $institucion instanceof Institucion
            ? $institucion->pais_id
            : null;

        return [
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('instituciones', 'nombre')
                    ->where('pais_id', $paisId)
                    ->ignore($institucionId),
            ],

            // El país no se puede cambiar: hay estudios de candidatos
            // enlazados por la FK compuesta (institucion_id, pais_id).
            'pais_id' => [
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
            'nombre.required' => 'El nombre de la institución es obligatorio.',
            'nombre.string' => 'El nombre de la institución debe ser una cadena de texto.',
            'nombre.max' => 'El nombre de la institución no puede superar los 255 caracteres.',
            'nombre.unique' => 'La institución ya existe en ese país.',

            'pais_id.prohibited' => 'El país de una institución no puede modificarse.',
            'estado.prohibited' => 'El estado no puede modificarse directamente.',
            'activo.prohibited' => 'El estado no puede modificarse directamente.',
        ];
    }
}
