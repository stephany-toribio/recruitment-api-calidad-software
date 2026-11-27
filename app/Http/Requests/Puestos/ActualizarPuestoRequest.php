<?php

namespace App\Http\Requests\Puestos;

use App\Models\Puesto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarPuestoRequest extends FormRequest
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
        $puesto = $this->route('puesto');

        $puestoId = $puesto instanceof Puesto
            ? $puesto->id
            : $puesto;

        return [
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:200',
                Rule::unique('puestos', 'nombre')
                    ->whereNull('candidato_id')
                    ->ignore($puestoId),
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

            'candidato_id.prohibited' => 'El dueño de un puesto no puede modificarse.',
            'estado.prohibited' => 'El estado no puede modificarse directamente.',
            'activo.prohibited' => 'El estado no puede modificarse directamente.',
        ];
    }
}
