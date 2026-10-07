<?php

namespace App\Http\Requests\Auth;

use App\Rules\PasswordSegura;
use Illuminate\Foundation\Http\FormRequest;

/** POST /api/v1/auth/reset-password */
class RestablecerPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'correo'      => ['required', 'email'],
            'reset_token' => ['required', 'string', 'size:64'],
            'password'    => ['required', 'string', 'confirmed', new PasswordSegura()],
        ];
    }

    public function messages(): array
    {
        return [
            'correo.required'      => 'El correo electrónico es obligatorio.',
            'correo.email'         => 'Debe ingresar un correo electrónico válido.',
            'reset_token.required' => 'El token de recuperación es obligatorio.',
            'reset_token.size'     => 'El token de recuperación no es válido.',
            'password.required'    => 'La nueva contraseña es obligatoria.',
            'password.confirmed'   => 'La confirmación de la contraseña no coincide.',
        ];
    }
}
