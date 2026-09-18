<?php

namespace App\Http\Requests\Empresas;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarEmpresaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'razon_social' => [
                'required',
                'string',
                'max:255',
            ],

            'ruc' => [
                'required',
                'digits:11',
                Rule::unique('empresas', 'ruc')
                    ->ignore($this->user()?->id, 'usuario_id'),
            ],

            'sitio_web' => [
                'required',
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
            'razon_social.required' => 'La razón social es obligatoria.',

            'ruc.required' => 'El RUC es obligatorio.',
            'ruc.digits' => 'El RUC debe tener exactamente 11 dígitos.',
            'ruc.unique' => 'El RUC ya está registrado.',

            'sitio_web.required' => 'El sitio web es obligatorio.',
            'sitio_web.url' => 'El sitio web debe ser una URL válida.',
        ];
    }
}
