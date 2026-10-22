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
            && (is_string($datos['titulo']) || $datos['titulo'] === null)
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
            'titulo' => ['sometimes', 'string', 'max:100'],

            'habilidades' => [
                'required_with:requisito',
                'array',
                'list',
                'max:10',
            ],

            'habilidades.*' => [
                'bail',
                'integer',
                'min:1',
                'distinct',
                new HabilidadSeleccionable,
            ],

            'requisito' => [
                'sometimes',
                'string',
                Rule::in(['must_have', 'nice_to_have']),
            ],

            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.string' => 'El título debe ser un texto.',
            'titulo.max' => 'El título a buscar no debe superar los 100 caracteres.',
            'habilidades.required_with' => 'Seleccione al menos una habilidad para filtrar por obligatoriedad.',
            'habilidades.array' => 'Las habilidades deben enviarse como lista.',
            'habilidades.list' => 'Las habilidades deben enviarse como una lista con índices consecutivos.',
            'habilidades.max' => 'No puede seleccionar más de 10 habilidades.',
            'habilidades.*.integer' => 'Cada habilidad debe ser un identificador entero.',
            'habilidades.*.min' => 'Cada habilidad debe ser un identificador positivo.',
            'habilidades.*.distinct' => 'No puede repetir habilidades.',
            'habilidades.*.exists' => 'La habilidad seleccionada no existe o está desactivada.',
            'requisito.string' => 'La obligatoriedad debe ser un texto.',
            'requisito.in' => 'La obligatoriedad debe ser must_have o nice_to_have. Para Ambas, omita el parámetro.',
            'page.integer' => 'La página debe ser un número entero.',
            'page.min' => 'La página debe ser mayor o igual a 1.',
        ];
    }
}
