<?php

namespace App\Http\Requests\Vacantes;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest para la creación de una vacante laboral.
 *
 * NOTA PARA EL EQUIPO (Sprint 1):
 * Ejemplo de validación avanzada con fechas, rangos numéricos y tipos de datos.
 */
class CrearVacanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // En Sprint 1: verificar que el usuario autenticado sea de tipo 'empresa'
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo'         => ['required', 'string', 'max:150'],
            'descripcion'    => ['required', 'string'],
            'modalidad'      => ['required', 'in:remoto,presencial,hibrido'],
            'salario_min'    => ['nullable', 'numeric', 'min:0'],
            'salario_max'    => ['nullable', 'numeric', 'gte:salario_min'],
            'fecha_cierre'   => ['nullable', 'date', 'after:today'],
            'habilidades'    => ['required', 'array', 'min:1'],
            'habilidades.*'  => ['integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required'      => 'El título del puesto es obligatorio.',
            'descripcion.required' => 'La descripción del empleo es obligatoria.',
            'modalidad.in'         => 'La modalidad debe ser remoto, presencial o híbrido.',
            'salario_max.gte'      => 'El salario máximo no puede ser menor al salario mínimo.',
            'fecha_cierre.after'   => 'La fecha de cierre de la vacante debe ser posterior al día de hoy.',
            'habilidades.required' => 'Debe asociar al menos una habilidad requerida para el puesto.',
        ];
    }
}
