<?php

namespace App\Http\Requests\Idiomas;

use App\Models\Idioma;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarIdiomaRequest extends FormRequest
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
        $idioma = $this->route('idioma');

        $idiomaId = $idioma instanceof Idioma
            ? $idioma->id
            : $idioma;

        return [
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('idiomas', 'nombre')->ignore($idiomaId),
            ],

            'activo' => [
                'prohibited',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del idioma es obligatorio.',
            'nombre.string' => 'El nombre del idioma debe ser una cadena de texto.',
            'nombre.max' => 'El nombre del idioma no puede superar los 50 caracteres.',
            'nombre.unique' => 'El idioma ya existe.',

            'activo.prohibited' => 'El estado no puede modificarse directamente.',
        ];
    }
}
