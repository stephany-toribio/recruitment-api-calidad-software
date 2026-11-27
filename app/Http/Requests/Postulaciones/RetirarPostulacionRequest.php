<?php

namespace App\Http\Requests\Postulaciones;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class RetirarPostulacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                foreach (array_keys($this->all()) as $campo) {
                    $validator->errors()->add(
                        (string) $campo,
                        'Este campo no está permitido al retirar una postulación.'
                    );
                }
            },
        ];
    }
}
