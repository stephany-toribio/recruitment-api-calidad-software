<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta petición.
     * En endpoints públicos como login siempre se retorna true.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Reglas de validación que se aplicarán a la petición.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Mensajes personalizados de validación en español para el frontend (Angular).
     */
    public function messages(): array
    {
        return [
            'email.required'    => 'El correo electrónico es obligatorio.',
            'email.email'       => 'Debe ingresar un correo electrónico válido.',
            'password.required' => 'La contraseña es obligatoria.',
        ];
    }
}
