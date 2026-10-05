<?php

namespace App\Http\Requests\Habilidades;

use App\Models\Habilidad;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class ActualizarHabilidadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $datos = [];

        if ($this->has('nombre') && is_string($this->nombre)) {
            $datos['nombre'] = preg_replace(
                '/\s+/u',
                ' ',
                trim($this->nombre)
            );
        }

        if ($this->has('tipo_habilidad') && is_string($this->tipo_habilidad)) {
            $datos['tipo_habilidad'] = strtolower(
                trim($this->tipo_habilidad)
            );
        }

        if (!empty($datos)) {
            $this->merge($datos);
        }
    }

    public function rules(): array
    {
        $habilidad = $this->route('habilidad');

        $habilidadId = $habilidad instanceof Habilidad
            ? $habilidad->id
            : $habilidad;

        return [
            'nombre' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('habilidades', 'nombre')
                    ->ignore($habilidadId),
            ],

            'tipo_habilidad' => [
                'sometimes',
                'required',
                'string',
                Rule::in(['tecnica', 'blanda']),
            ],

            'area_estudio_id' => [
                'sometimes',
                'nullable',
                'integer',
                Rule::exists('areas_estudio', 'id')
                    ->where(
                        fn ($query) => $query->where('activo', true)
                    ),
            ],

            'activo' => [
                'prohibited',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (
                    $validator->errors()->has('tipo_habilidad') ||
                    $validator->errors()->has('area_estudio_id')
                ) {
                    return;
                }

                $habilidad = $this->route('habilidad');

                if (!$habilidad instanceof Habilidad) {
                    return;
                }

                $tipoFinal = $this->has('tipo_habilidad')
                    ? $this->input('tipo_habilidad')
                    : $habilidad->tipo_habilidad;

                $areaFinal = $this->exists('area_estudio_id')
                    ? $this->input('area_estudio_id')
                    : $habilidad->area_estudio_id;

                if ($tipoFinal === 'tecnica' && $areaFinal === null) {
                    $validator->errors()->add(
                        'area_estudio_id',
                        'Las habilidades técnicas deben pertenecer a un área de estudio.'
                    );
                }

                if ($tipoFinal === 'blanda' && $areaFinal !== null) {
                    $validator->errors()->add(
                        'area_estudio_id',
                        'Las habilidades blandas no deben pertenecer a un área de estudio.'
                    );
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la habilidad es obligatorio.',
            'nombre.string' => 'El nombre de la habilidad debe ser una cadena de texto.',
            'nombre.max' => 'El nombre de la habilidad no puede superar los 100 caracteres.',
            'nombre.unique' => 'La habilidad ya existe.',

            'tipo_habilidad.required' => 'El tipo de habilidad es obligatorio.',
            'tipo_habilidad.string' => 'El tipo de habilidad debe ser una cadena de texto.',
            'tipo_habilidad.in' => 'El tipo de habilidad debe ser tecnica o blanda.',

            'area_estudio_id.integer' => 'El área de estudio debe ser un identificador válido.',
            'area_estudio_id.exists' => 'El área de estudio seleccionada no existe o está inactiva.',

            'activo.prohibited' => 'El estado no puede modificarse directamente.',
        ];
    }
}
