<?php

namespace App\Http\Requests\Postulaciones;

use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PostularVacanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'vacante_id' => [
                'bail',
                'required',
                function (
                    string $attribute,
                    mixed $value,
                    Closure $fail
                ): void {
                    if (! is_int($value) && ! is_string($value)) {
                        $fail(
                            'La vacante debe ser un identificador entero positivo.'
                        );
                    }
                },
                'integer',
                'min:1',
                'max:'.PHP_INT_MAX,
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (array_keys($this->all()) as $campo) {
                    if ($campo !== 'vacante_id') {
                        $validator->errors()->add(
                            (string) $campo,
                            'Este campo no está permitido al postular.'
                        );
                    }
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'vacante_id.required' => 'La vacante a postular es obligatoria.',
            'vacante_id.integer' => 'La vacante debe ser un identificador entero.',
            'vacante_id.min' => 'El identificador de la vacante debe ser mayor que cero.',
            'vacante_id.max' => 'El identificador de la vacante supera el máximo permitido.',
        ];
    }
}
