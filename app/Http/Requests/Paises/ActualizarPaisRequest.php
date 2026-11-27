<?php

namespace App\Http\Requests\Paises;

use App\Models\Pais;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarPaisRequest extends FormRequest
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
        $pais = $this->route('pais');

        $paisId = $pais instanceof Pais ? $pais->id : $pais;

        return [
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('paises', 'nombre')->ignore($paisId),
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
