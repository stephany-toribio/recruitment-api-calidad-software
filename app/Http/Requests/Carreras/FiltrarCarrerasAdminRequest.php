<?php

namespace App\Http\Requests\Carreras;

use Illuminate\Foundation\Http\FormRequest;

// Filtros del listado de carreras del administrador (incluye las inactivas)
class FiltrarCarrerasAdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tipo_estudio_id' => ['sometimes', 'integer', 'exists:tipos_estudio,id'],
            'activo'          => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'tipo_estudio_id.integer' => 'El tipo de estudio debe ser un identificador numérico.',
            'tipo_estudio_id.exists'  => 'El tipo de estudio indicado no existe.',
            'activo.boolean'          => 'El filtro activo debe ser 1 (activas) o 0 (inactivas).',
        ];
    }
}
