<?php

namespace App\Http\Requests\Idiomas;

use Illuminate\Foundation\Http\FormRequest;

// Filtro del listado de idiomas del administrador (incluye los inactivos)
class FiltrarIdiomasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'activo' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'activo.boolean' => 'El filtro activo debe ser 1 (activos) o 0 (inactivos).',
        ];
    }
}
