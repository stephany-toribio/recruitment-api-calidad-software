<?php

namespace App\Http\Requests\Organizaciones;

use App\Models\Organizacion;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarOrganizacionRequest extends FormRequest
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
        $organizacion = $this->route('organizacion');

        $organizacionId = $organizacion instanceof Organizacion
            ? $organizacion->id
            : $organizacion;

        $paisId = $organizacion instanceof Organizacion
            ? $organizacion->pais_id
            : null;

        return [
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:200',
                Rule::unique('organizaciones', 'nombre')
                    ->where('pais_id', $paisId)
                    ->whereNull('candidato_id')
                    ->ignore($organizacionId),
            ],

            // El país no se puede cambiar: hay experiencias enlazadas por la FK compuesta (organizacion_id, pais_id).
            'pais_id' => [
                'prohibited',
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

            'pais_id.prohibited' => 'El país de una organización no puede modificarse.',
            'candidato_id.prohibited' => 'El dueño de una organización no puede modificarse.',
            'estado.prohibited' => 'El estado no puede modificarse directamente.',
            'activo.prohibited' => 'El estado no puede modificarse directamente.',
        ];
    }
}
