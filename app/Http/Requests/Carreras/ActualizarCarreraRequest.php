<?php

namespace App\Http\Requests\Carreras;

use App\Models\Carrera;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarCarreraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;//
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
        $carrera = $this->route('carrera');

        $carreraId = $carrera instanceof Carrera
            ? $carrera->id
            : $carrera;

        $tipoId = $carrera instanceof Carrera
            ? $carrera->tipo_estudio_id
            : null;

        return [
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:255',
                Rule::unique('carreras', 'nombre')
                    ->where('tipo_estudio_id', $tipoId)
                    ->ignore($carreraId),
            ],

            
            'tipo_estudio_id' => [
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
            'nombre.required' => 'El nombre de la carrera es obligatorio.',
            'nombre.string' => 'El nombre de la carrera debe ser una cadena de texto.',
            'nombre.max' => 'El nombre de la carrera no puede superar los 255 caracteres.',
            'nombre.unique' => 'La carrera ya existe para ese tipo de estudio.',

            'tipo_estudio_id.prohibited' => 'El tipo de estudio de una carrera no puede modificarse.',
            'activo.prohibited' => 'El estado no puede modificarse directamente.',
        ];
    }
}
