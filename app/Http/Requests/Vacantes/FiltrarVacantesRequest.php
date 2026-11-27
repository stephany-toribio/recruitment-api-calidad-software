<?php

namespace App\Http\Requests\Vacantes;

use App\Rules\HabilidadSeleccionable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FiltrarVacantesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $datos = $this->all();

        if (
            array_key_exists('titulo', $datos)
            && (
                is_string($datos['titulo'])
                || $datos['titulo'] === null
            )
        ) {
            $titulo = trim($datos['titulo'] ?? '');

            if ($titulo === '') {
                unset($datos['titulo']);
            } else {
                $datos['titulo'] = $titulo;
            }
        }

        $this->replace($datos);
    }

    public function rules(): array
    {
        return [
            'titulo' => [
                'sometimes',
                'string',
                'max:100',
            ],

            'habilidades' => [
                'sometimes',
                'array',
                'list',
                'max:3',
            ],

            'habilidades.*' => [
                'bail',
                'required',
                'integer',
                'min:1',
                'distinct',
                new HabilidadSeleccionable,
            ],

            'niveles_puesto' => [
                'sometimes',
                'array',
                'list',
            ],

            'niveles_puesto.*' => [
                'bail',
                'required',
                'integer',
                'min:1',
                'distinct',
                Rule::exists('niveles_puesto', 'id')
                    ->whereNull('candidato_id')
                    ->where('estado', 'activo'),
            ],

            'area_estudio_id' => [
                'bail',
                'sometimes',
                'integer',
                'min:1',
                Rule::exists('areas_estudio', 'id')
                    ->where('activo', true),
            ],

            'solo_remoto' => [
                'sometimes',
                'boolean',
            ],

            'salario_minimo' => [
                'bail',
                'sometimes',
                'integer',
                Rule::in([1500, 3000, 5000, 7000, 9000]),
            ],

            'page' => [
                'sometimes',
                'integer',
                'min:1',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.string' => 'El título debe ser un texto.',
            'titulo.max' => 'El título no debe superar los 100 caracteres.',

            'habilidades.array' => 'Las habilidades deben enviarse como una lista.',
            'habilidades.list' => 'Las habilidades deben tener índices consecutivos.',
            'habilidades.max' => 'No puede seleccionar más de 3 habilidades.',

            'habilidades.*.required' => 'Cada habilidad debe tener un identificador.',
            'habilidades.*.integer' => 'Cada habilidad debe ser un identificador entero.',
            'habilidades.*.min' => 'Cada habilidad debe ser un identificador positivo.',
            'habilidades.*.distinct' => 'No puede repetir habilidades.',

            'niveles_puesto.array' => 'Los niveles deben enviarse como una lista.',
            'niveles_puesto.list' => 'Los niveles deben tener índices consecutivos.',

            'niveles_puesto.*.required' => 'Cada nivel debe tener un identificador.',
            'niveles_puesto.*.integer' => 'Cada nivel debe ser un identificador entero.',
            'niveles_puesto.*.min' => 'Cada nivel debe ser un identificador positivo.',
            'niveles_puesto.*.distinct' => 'No puede repetir niveles.',
            'niveles_puesto.*.exists' => 'Seleccione un nivel global habilitado.',

            'area_estudio_id.integer' => 'El área debe ser un identificador entero.',
            'area_estudio_id.min' => 'El identificador del área debe ser positivo.',
            'area_estudio_id.exists' => 'Seleccione un área existente y activa.',

            'solo_remoto.boolean' => 'El filtro remoto debe enviarse como 0 o 1.',

            'salario_minimo.integer' => 'El salario mínimo debe ser un entero.',
            'salario_minimo.in' => 'Seleccione 1500, 3000, 5000, 7000 o 9000 soles.',

            'page.integer' => 'La página debe ser un número entero.',
            'page.min' => 'La página debe ser mayor o igual a 1.',
        ];
    }
}
