<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta petición.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación para el registro base de un usuario.
     * En Sprint 1 se ajustará según el tipo de usuario (candidato o empresa).
     */
    public function rules(): array
    {
        return [
            'name'     => ['required', 'string', 'max:120'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ];
    }

    /**
     * Mensajes personalizados de error.
     */
    public function messages(): array
    {
        return [
            'name.required'      => 'El nombre es obligatorio.',
            'email.required'     => 'El correo electrónico es obligatorio.',
            'email.unique'       => 'Este correo electrónico ya se encuentra registrado.',
            'password.required'   => 'La contraseña es obligatoria.',
            'password.confirmed'  => 'La confirmación de contraseña no coincide.',
            'password.min'        => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }
}
