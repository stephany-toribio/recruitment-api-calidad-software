<?php

namespace App\Http\Requests\Moderacion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Aprobación masiva. Aquí solo se valida el formato de la lista.
 * Si cada vacante existe y está pendiente lo revisa RevisionVacanteService:
 * con "todo o nada" responde 422 BULK_APPROVAL_REJECTED, no el 422 de validación.
 */
class AprobarMasivoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vacantes' => [
                'required',
                'array',
                'list',
                'min:1',
            ],

            'vacantes.*' => [
                'bail',
                'integer',
                'min:1',
                'distinct',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'vacantes.required' => 'Seleccione al menos una vacante.',
            'vacantes.array' => 'Las vacantes deben enviarse como lista.',
            'vacantes.list' => 'Las vacantes deben enviarse como lista.',
            'vacantes.min' => 'Seleccione al menos una vacante.',

            'vacantes.*.integer' => 'Cada vacante debe ser un identificador numérico.',
            'vacantes.*.min' => 'Cada vacante debe ser un identificador numérico.',
            'vacantes.*.distinct' => 'No puede repetir vacantes.',
        ];
    }
}
