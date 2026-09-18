<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class CambiarPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'password_actual'             => ['required', 'string'],
            'password_nuevo'              => ['required', 'confirmed', Password::min(8)],
            'password_nuevo_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'password_actual.required'             => 'Debes ingresar tu contraseña actual.',
            'password_nuevo.required'              => 'La nueva contraseña es obligatoria.',
            'password_nuevo.confirmed'             => 'La confirmación de la nueva contraseña no coincide.',
            'password_nuevo.min'                   => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password_nuevo_confirmation.required' => 'Debes confirmar la nueva contraseña.',
        ];
    }
}
