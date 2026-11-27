<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

/** POST /api/v1/auth/verify-reset-code */
class VerificarCodigoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'correo' => ['required', 'email'],
            'codigo' => ['required', 'string', 'digits:6'],
        ];
    }

    public function messages(): array
    {
        return [
            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.email'    => 'Debe ingresar un correo electrónico válido.',
            'codigo.required' => 'El código es obligatorio.',
            'codigo.string'   => 'El código debe enviarse como texto.',
            'codigo.digits'   => 'El código debe tener exactamente 6 dígitos.',
        ];
    }
}
