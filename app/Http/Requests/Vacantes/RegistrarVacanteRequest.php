<?php

namespace App\Http\Requests\Vacantes;

use App\Models\Empleo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarVacanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Las rutas aplican auth:sanctum y rol:empresa.
        return true;
    }

    public function rules(): array
    {
        return [
            'titulo' => [
                'bail',
                'required',
                'string',
                'max:100',
                'not_regex:/^\s*$/u',
            ],

            'descripcion' => [
                'bail',
                'required',
                'string',
                'min:50',
                'not_regex:/^\s*$/u',
            ],

            'nivel_experiencia' => [
                'bail',
                'required',
                'string',
                Rule::in(array_keys(Empleo::NIVELES)),
            ],

            'salario_min' => [
                'bail',
                'required',
                'numeric',
                'gt:0',
                'decimal:0,2',
                'max:99999999.99',
            ],

            'salario_max' => [
                'bail',
                'required',
                'numeric',
                'decimal:0,2',
                'gte:salario_min',
                'max:99999999.99',
            ],

            'fecha_inicio_vigencia' => [
                'bail',
                'required',
                'date_format:Y-m-d',
                'after_or_equal:' . now(Empleo::ZONA_VIGENCIA)->toDateString(),
            ],

            'fecha_fin_vigencia' => [
                'bail',
                'required',
                'date_format:Y-m-d',
                'after:fecha_inicio_vigencia',
            ],

            'max_contrataciones' => [
                'bail',
                'required',
                'integer',
                'min:1',
                'max:65535',
            ],

            'habilidades' => [
                'bail',
                'required',
                'array',
                'list',
                'min:1',
                'max:10',
            ],

            'habilidades.*' => [
                'bail',
                'required',
                'array:habilidad_id,requisito',
            ],

            'habilidades.*.habilidad_id' => [
                'bail',
                'required',
                'integer',
                'distinct',
                Rule::exists('habilidades', 'id')->where('activo', true),
            ],

            'habilidades.*.requisito' => [
                'bail',
                'required',
                'string',
                Rule::in(['must_have', 'nice_to_have']),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'titulo.required' => 'El título es obligatorio.',
            'titulo.string' => 'El título debe ser un texto.',
            'titulo.max' => 'El título no debe superar los 100 caracteres.',
            'titulo.not_regex' => 'El título no puede contener solo espacios.',

            'descripcion.required' => 'La descripción es obligatoria.',
            'descripcion.string' => 'La descripción debe ser un texto.',
            'descripcion.min' => 'La descripción debe tener al menos 50 caracteres.',
            'descripcion.not_regex' => 'La descripción no puede contener solo espacios.',

            'nivel_experiencia.required' => 'El nivel de experiencia es obligatorio.',
            'nivel_experiencia.string' => 'El nivel de experiencia seleccionado no es válido.',
            'nivel_experiencia.in' => 'El nivel de experiencia seleccionado no es válido.',

            'salario_min.required' => 'El salario mínimo es obligatorio.',
            'salario_min.numeric' => 'El salario mínimo debe ser un número mayor que 0.',
            'salario_min.gt' => 'El salario mínimo debe ser un número mayor que 0.',
            'salario_min.decimal' => 'El salario mínimo debe tener hasta 2 decimales.',
            'salario_min.max' => 'El salario mínimo no debe superar 99999999.99 soles.',

            'salario_max.required' => 'El salario máximo es obligatorio.',
            'salario_max.numeric' => 'El salario máximo debe ser un número.',
            'salario_max.decimal' => 'El salario máximo debe tener hasta 2 decimales.',
            'salario_max.gte' => 'El salario máximo no puede ser menor que el salario mínimo.',
            'salario_max.max' => 'El salario máximo no debe superar 99999999.99 soles.',

            'fecha_inicio_vigencia.required' => 'La fecha de inicio de vigencia es obligatoria.',
            'fecha_inicio_vigencia.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
            'fecha_inicio_vigencia.after_or_equal' => 'La fecha de inicio de vigencia no puede ser anterior a hoy.',

            'fecha_fin_vigencia.required' => 'La fecha de fin de vigencia es obligatoria.',
            'fecha_fin_vigencia.date_format' => 'La fecha debe tener el formato AAAA-MM-DD.',
            'fecha_fin_vigencia.after' => 'La fecha de fin de vigencia debe ser posterior a la fecha de inicio.',

            'max_contrataciones.required' => 'El número máximo de contrataciones es obligatorio.',
            'max_contrataciones.integer' => 'El número máximo de contrataciones debe ser un entero mayor o igual a 1.',
            'max_contrataciones.min' => 'El número máximo de contrataciones debe ser un entero mayor o igual a 1.',
            'max_contrataciones.max' => 'El número máximo de contrataciones no debe superar 65535.',

            'habilidades.required' => 'Debe seleccionar al menos 1 habilidad.',
            'habilidades.array' => 'Las habilidades deben enviarse como una lista.',
            'habilidades.list' => 'Las habilidades deben enviarse como una lista.',
            'habilidades.min' => 'Debe seleccionar al menos 1 habilidad.',
            'habilidades.max' => 'No puede seleccionar más de 10 habilidades.',

            'habilidades.*.required' => 'Debe proporcionar los datos de cada habilidad.',
            'habilidades.*.array' => 'Cada habilidad debe contener habilidad_id y requisito.',

            'habilidades.*.habilidad_id.required' => 'La habilidad es obligatoria.',
            'habilidades.*.habilidad_id.integer' => 'El identificador de la habilidad debe ser un entero.',
            'habilidades.*.habilidad_id.distinct' => 'No puede repetir habilidades.',
            'habilidades.*.habilidad_id.exists' => 'La habilidad seleccionada no existe o está inactiva.',

            'habilidades.*.requisito.required' => 'Indique si la habilidad es obligatoria (must_have) o deseable (nice_to_have).',
            'habilidades.*.requisito.string' => 'Indique si la habilidad es obligatoria (must_have) o deseable (nice_to_have).',
            'habilidades.*.requisito.in' => 'Indique si la habilidad es obligatoria (must_have) o deseable (nice_to_have).',
        ];
    }
}