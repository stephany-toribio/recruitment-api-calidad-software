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
            'encargado_empresa' => [
                'required',
                'string',
                'max:255',
            ],

            'razon_social' => [
                'required',
                'string',
                'max:255',
            ],

            'sector' => [
                'required',
                'string',
                'max:100',
                Rule::in([
                    'Salud y Medicina',
                    'Ciencias e Investigación',
                    'Tecnología e Informática',
                    'Educación',
                    'Administración y Negocios',
                    'Comunicación y Marketing',
                    'Diseño y Artes',
                    'Derecho',
                    'Ingeniería',
                ]),
            ],

            'ruc' => [
                'required',
                'digits:11',
                Rule::unique('empresas', 'ruc')
                    ->ignore($this->user()?->id, 'usuario_id'),
            ],

            'sitio_web' => [
                'nullable',
                'url',
                'max:255',
            ],

            'descripcion' => [
                'nullable',
                'string',
                'max:250',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'encargado_empresa.required' => 'El encargado de la empresa es obligatorio.',
            'encargado_empresa.string' => 'El encargado de la empresa debe ser un texto.',
            'encargado_empresa.max' => 'El encargado de la empresa no debe superar los 255 caracteres.',

            'razon_social.required' => 'La razón social es obligatoria.',
            'razon_social.string' => 'La razón social debe ser un texto.',
            'razon_social.max' => 'La razón social no debe superar los 255 caracteres.',

            'sector.required' => 'El sector es obligatorio.',
            'sector.string' => 'El sector debe ser un texto.',
            'sector.max' => 'El sector no debe superar los 100 caracteres.',
            'sector.in' => 'El sector seleccionado no es válido.',

            'ruc.required' => 'El RUC es obligatorio.',
            'ruc.digits' => 'El RUC debe tener exactamente 11 dígitos.',
            'ruc.unique' => 'El RUC ya está registrado.',

            'sitio_web.url' => 'El sitio web debe ser una URL válida.',
            'sitio_web.max' => 'El sitio web no debe superar los 255 caracteres.',

            'descripcion.string' => 'La descripción debe ser un texto.',
            'descripcion.max' => 'La descripción no debe superar los 250 caracteres.',
        ];
    }
}
