<?php

namespace App\Http\Requests\Pagos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Datos de la tarjeta del pago simulado.
 * Solo se aceptan las tarjetas de prueba de config/pago_vacantes.php.
 * El monto no se recibe: lo calcula el servidor. El número completo y el CVV nunca se guardan.
 */
class PagarVacanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('titular') && is_string($this->titular)) {
            $this->merge([
                'titular' => preg_replace('/\s+/u', ' ', trim($this->titular)),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'titular' => [
                'required',
                'string',
                'max:100',
            ],

            'numero_tarjeta' => [
                'bail',
                'required',
                'string',
                'digits:16',
                Rule::in(array_keys(config('pago_vacantes.tarjetas_prueba'))),
            ],

            'vencimiento' => [
                'bail',
                'required',
                'string',
                'regex:/^(0[1-9]|1[0-2])\/\d{2}$/',
            ],

            'cvv' => [
                'required',
                'string',
                'digits:3',
            ],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('vencimiento')) {
                    return;
                }

                // La tarjeta sirve hasta el último día del mes indicado
                $vence = Carbon::createFromFormat('!m/y', $this->input('vencimiento'))->endOfMonth();

                if ($vence->isPast()) {
                    $validator->errors()->add('vencimiento', 'La tarjeta está vencida.');
                }
            },
        ];
    }

    public function messages(): array
    {
        return [
            'titular.required' => 'El titular es obligatorio.',
            'titular.string' => 'El titular debe ser una cadena de texto.',
            'titular.max' => 'El titular no debe superar los 100 caracteres.',

            'numero_tarjeta.required' => 'El número de tarjeta es obligatorio.',
            'numero_tarjeta.string' => 'El número de tarjeta debe enviarse como texto.',
            'numero_tarjeta.digits' => 'El número de tarjeta debe tener 16 dígitos.',
            'numero_tarjeta.in' => 'Use una tarjeta de prueba: 4111111111111111 (aprobada) o 4000000000000002 (rechazada).',

            'vencimiento.required' => 'El vencimiento es obligatorio.',
            'vencimiento.string' => 'El vencimiento debe enviarse como texto.',
            'vencimiento.regex' => 'El vencimiento debe tener el formato MM/AA.',

            'cvv.required' => 'El CVV es obligatorio.',
            'cvv.string' => 'El CVV debe enviarse como texto.',
            'cvv.digits' => 'El CVV debe tener 3 dígitos.',
        ];
    }
}
