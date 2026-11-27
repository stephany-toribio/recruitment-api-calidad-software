<?php

namespace App\Http\Requests\Auth;

use App\Rules\PasswordSegura;
use Illuminate\Foundation\Http\FormRequest;

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
            'password_nuevo'              => ['required', 'confirmed', new PasswordSegura()],
            'password_nuevo_confirmation' => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'password_actual.required'             => 'Debes ingresar tu contraseña actual.',
            'password_nuevo.required'              => 'La nueva contraseña es obligatoria.',
            'password_nuevo.confirmed'             => 'La confirmación de la nueva contraseña no coincide.',
            'password_nuevo_confirmation.required' => 'Debes confirmar la nueva contraseña.',
        ];
    }
}