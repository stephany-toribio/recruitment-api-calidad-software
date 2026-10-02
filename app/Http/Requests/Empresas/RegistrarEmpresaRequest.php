<?php

namespace App\Http\Requests\Empresas;

use Illuminate\Foundation\Http\FormRequest;

class RegistrarEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'correo' => [
                'required',
                'email',
                'max:255',
                'unique:usuarios,correo',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@#$%&]/',
            ],

            'razon_social' => [
                'required',
                'string',
                'max:255',
            ],

            'ruc' => [
                'required',
                'digits:11',
                'unique:empresas,ruc',
            ],

            'sitio_web' => [
                'nullable',
                'url',
                'max:255',
            ],

            'descripcion' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'correo.required' => 'El correo es obligatorio.',
            'correo.email' => 'El correo debe tener un formato válido.',
            'correo.unique' => 'El correo ya está registrado.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.regex' => 'La contraseña debe incluir al menos una mayúscula, un número y uno de los caracteres especiales @, #, $, % o &.',

            'razon_social.required' => 'La razón social es obligatoria.',

            'ruc.required' => 'El RUC es obligatorio.',
            'ruc.digits' => 'El RUC debe tener exactamente 11 dígitos.',
            'ruc.unique' => 'El RUC ya está registrado.',

            'sitio_web.url' => 'El sitio web debe ser una URL válida.',
        ];
    }
}
