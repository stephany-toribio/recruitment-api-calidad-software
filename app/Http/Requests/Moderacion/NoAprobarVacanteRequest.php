<?php

namespace App\Http\Requests\Moderacion;

use Illuminate\Foundation\Http\FormRequest;

// No aprobar una vacante. El motivo es obligatorio porque la decisión es definitiva.
class NoAprobarVacanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        // Un motivo de solo espacios queda vacío y lo rechaza 'required'
        if ($this->has('motivo') && is_string($this->motivo)) {
            $this->merge([
                'motivo' => trim($this->motivo),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'motivo' => [
                'required',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'motivo.required' => 'El motivo de no aprobación es obligatorio.',
            'motivo.string' => 'El motivo debe ser una cadena de texto.',
            'motivo.max' => 'El motivo no debe superar los 500 caracteres.',
        ];
    }
}
