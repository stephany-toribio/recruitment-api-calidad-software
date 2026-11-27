<?php

namespace App\Http\Requests\NivelesPuesto;

use App\Models\NivelPuesto;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarNivelPuestoRequest extends FormRequest
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
        $nivel = $this->route('nivelPuesto');

        $nivelId = $nivel instanceof NivelPuesto
            ? $nivel->id
            : $nivel;

        return [
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('niveles_puesto', 'nombre')
                    ->whereNull('candidato_id')
                    ->ignore($nivelId),
            ],

            'orden' => [
                'sometimes',
                'required',
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

            'orden.required' => 'El orden es obligatorio.',
            'orden.integer' => 'El orden debe ser un número entero.',
            'orden.min' => 'El orden debe ser al menos 1.',
            'orden.max' => 'El orden no puede superar 999.',

            'candidato_id.prohibited' => 'El dueño de un nivel no puede modificarse.',
            'estado.prohibited' => 'El estado no puede modificarse directamente.',
            'activo.prohibited' => 'El estado no puede modificarse directamente.',
        ];
    }
}
