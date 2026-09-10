<?php

namespace App\Http\Requests\Postulaciones;

use Illuminate\Foundation\Http\FormRequest;

/**
 * FormRequest para que un candidato postule a una vacante laboral.
 */
class PostularVacanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // En Sprint 1: verificar que el usuario autenticado sea de tipo 'candidato'
        return true;
    }

    public function rules(): array
    {
        return [
            'vacante_id'         => ['required', 'integer'], // En Sprint 1: 'exists:vacantes,id'
            'carta_presentacion' => ['nullable', 'string', 'max:1000'],
            'cv_adjunto'         => ['nullable', 'file', 'mimes:pdf', 'max:5120'], // Máximo 5MB en PDF
        ];
    }

    public function messages(): array
    {
        return [
            'vacante_id.required' => 'La vacante a postular es obligatoria.',
            'cv_adjunto.mimes'    => 'El currículum debe ser un archivo en formato PDF.',
            'cv_adjunto.max'      => 'El archivo no debe exceder los 5 MB.',
        ];
    }
}
