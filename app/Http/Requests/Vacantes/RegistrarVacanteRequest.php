<?php

namespace App\Http\Requests\Vacantes;

use App\Models\Empleo;
use App\Rules\HabilidadSeleccionable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarVacanteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $datos = $this->all();

        foreach (['titulo', 'descripcion', 'ubicacion'] as $campo) {
            if (
                array_key_exists($campo, $datos)
                && is_string($datos[$campo])
            ) {
                $datos[$campo] = trim($datos[$campo]);
            }
        }

        if (
            array_key_exists('ubicacion', $datos)
            && $datos['ubicacion'] === ''
        ) {
            $datos['ubicacion'] = null;
        }

        $this->replace($datos);
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

            'nivel_puesto_id' => [
                'bail',
                'required',
                'integer',
                'min:1',
                Rule::exists('niveles_puesto', 'id')
                    ->whereNull('candidato_id')
                    ->where('estado', 'activo'),
            ],

            'area_estudio_id' => [
                'bail',
                'required',
                'integer',
                'min:1',
                Rule::exists('areas_estudio', 'id')
                    ->where('activo', true),
            ],

            'tipo_empleo' => [
                'bail',
                'required',
                'string',
                Rule::in(['presencial', 'remoto', 'hibrido']),
            ],

            'ubicacion' => [
                'bail',
                Rule::requiredIf(
                    fn () => in_array(
                        $this->input('tipo_empleo'),
                        ['presencial', 'hibrido'],
                        true
                    )
                ),
                'nullable',
                'string',
                'max:255',
                'not_regex:/^\s*$/u',
            ],

            'salario_min' => [
                'bail',
                'required_with:salario_max',
                'nullable',
                'numeric',
                'gt:0',
                'decimal:0,2',
                'max:99999999.99',
            ],

            'salario_max' => [
                'bail',
                'required_with:salario_min',
                'nullable',
                'numeric',
                'gt:0',
                'decimal:0,2',
                'gte:salario_min',
                'max:99999999.99',
            ],

            'fecha_inicio_vigencia' => [
                'bail',
                'required',
                'date_format:Y-m-d',
                'after_or_equal:'.now(Empleo::ZONA_VIGENCIA)
                    ->toDateString(),
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
                'min:1',
                'distinct',
                new HabilidadSeleccionable,
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

            'nivel_puesto_id.required' => 'Seleccione el nivel del puesto.',
            'nivel_puesto_id.integer' => 'El nivel debe ser un identificador entero.',
            'nivel_puesto_id.min' => 'El identificador del nivel debe ser positivo.',
            'nivel_puesto_id.exists' => 'Seleccione un nivel global habilitado.',

            'area_estudio_id.required' => 'Seleccione el área profesional.',
            'area_estudio_id.integer' => 'El área debe ser un identificador entero.',
            'area_estudio_id.min' => 'El identificador del área debe ser positivo.',
            'area_estudio_id.exists' => 'Seleccione un área existente y activa.',

            'tipo_empleo.required' => 'Seleccione la modalidad de trabajo.',
            'tipo_empleo.string' => 'La modalidad debe ser un texto.',
            'tipo_empleo.in' => 'La modalidad debe ser presencial, remoto o hibrido.',

            'ubicacion.required' => 'La ubicación es obligatoria para presencial e híbrido.',
            'ubicacion.string' => 'La ubicación debe ser un texto.',
            'ubicacion.max' => 'La ubicación no debe superar los 255 caracteres.',
            'ubicacion.not_regex' => 'La ubicación no puede contener solo espacios.',

            'salario_min.required_with' => 'Informe el salario mínimo cuando indique el máximo.',
            'salario_min.numeric' => 'El salario mínimo debe ser numérico.',
            'salario_min.gt' => 'El salario mínimo debe ser mayor que cero.',
            'salario_min.decimal' => 'El salario mínimo debe tener hasta 2 decimales.',
            'salario_min.max' => 'El salario mínimo no debe superar 99999999.99 soles.',

            'salario_max.required_with' => 'Informe el salario máximo cuando indique el mínimo.',
            'salario_max.numeric' => 'El salario máximo debe ser numérico.',
            'salario_max.gt' => 'El salario máximo debe ser mayor que cero.',
            'salario_max.decimal' => 'El salario máximo debe tener hasta 2 decimales.',
            'salario_max.gte' => 'El salario máximo no puede ser menor que el mínimo.',
            'salario_max.max' => 'El salario máximo no debe superar 99999999.99 soles.',

            'fecha_inicio_vigencia.required' => 'La fecha de inicio es obligatoria.',
            'fecha_inicio_vigencia.date_format' => 'La fecha de inicio debe tener el formato AAAA-MM-DD.',
            'fecha_inicio_vigencia.after_or_equal' => 'La fecha de inicio no puede ser anterior a hoy en Lima.',

            'fecha_fin_vigencia.required' => 'La fecha de fin es obligatoria.',
            'fecha_fin_vigencia.date_format' => 'La fecha de fin debe tener el formato AAAA-MM-DD.',
            'fecha_fin_vigencia.after' => 'La fecha de fin debe ser posterior a la fecha de inicio.',

            'max_contrataciones.required' => 'El máximo de contrataciones es obligatorio.',
            'max_contrataciones.integer' => 'El máximo de contrataciones debe ser un entero.',
            'max_contrataciones.min' => 'Debe permitir al menos una contratación.',
            'max_contrataciones.max' => 'El máximo de contrataciones no debe superar 65535.',

            'habilidades.required' => 'Seleccione al menos una habilidad.',
            'habilidades.array' => 'Las habilidades deben enviarse como una lista.',
            'habilidades.list' => 'Las habilidades deben tener índices consecutivos.',
            'habilidades.min' => 'Seleccione al menos una habilidad.',
            'habilidades.max' => 'No puede asociar más de 10 habilidades a la vacante.',

            'habilidades.*.required' => 'Proporcione los datos de cada habilidad.',
            'habilidades.*.array' => 'Cada habilidad debe contener únicamente habilidad_id y requisito.',

            'habilidades.*.habilidad_id.required' => 'El identificador de la habilidad es obligatorio.',
            'habilidades.*.habilidad_id.integer' => 'El identificador de la habilidad debe ser entero.',
            'habilidades.*.habilidad_id.min' => 'El identificador de la habilidad debe ser positivo.',
            'habilidades.*.habilidad_id.distinct' => 'No puede repetir habilidades.',

            'habilidades.*.requisito.required' => 'Indique si la habilidad es obligatoria o deseable.',
            'habilidades.*.requisito.string' => 'El requisito debe ser un texto.',
            'habilidades.*.requisito.in' => 'El requisito debe ser must_have o nice_to_have.',
        ];
    }
}
