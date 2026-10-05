<?php

namespace App\Http\Requests\Candidatos;

use App\Exceptions\BusinessException;
use App\Rules\PasswordSegura;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegistrarCandidatoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'          => ['required', 'string', 'max:120'],
            'apellidos'       => ['required', 'string', 'max:255'],
            'dni'             => ['required', 'string', 'size:8', 'unique:candidatos,dni'],
            'fecha_nacimiento'=> ['required', 'date'],
            'genero'          => ['nullable', 'in:masculino,femenino,otro,prefiero no decir'],
            'nivel_educativo' => ['required', 'in:secundaria,tecnico,universitario,posgrado'],
            'correo'          => ['required', 'email', 'max:120', 'unique:usuarios,correo'],
            'password'        => ['required', 'confirmed', new PasswordSegura(), 'max:120'],
            'celular'         => ['required', 'string', 'max:20'],
            'disponibilidad'  => ['required', 'in:inmediata,1_semana,1_mes'],

            'habilidades' => [
                'required',
                'array',
                'min:1',
                'max:15',
            ],

            'habilidades.*' => [
                'required',
                'integer',
                'distinct',
                Rule::exists('habilidades', 'id')
                    ->where(function (Builder $query) {
                        $query->where('activo', true)
                            ->where(function (Builder $query) {
                                $query->where('tipo_habilidad', 'blanda')
                                    ->orWhere(function (Builder $query) {
                                        $query->where('tipo_habilidad', 'tecnica')
                                            ->whereExists(function (Builder $query) {
                                                $query->selectRaw('1')
                                                    ->from('areas_estudio')
                                                    ->whereColumn(
                                                        'areas_estudio.id',
                                                        'habilidades.area_estudio_id'
                                                    )
                                                    ->where('areas_estudio.activo', true);
                                            });
                                    });
                            });
                    }),
            ],

            'carreras'                    => ['nullable', 'array'],
            'carreras.*.carrera_id'       => ['required_with:carreras', 'integer', 'exists:carreras,id'],
            'carreras.*.institucion'      => ['required_with:carreras', 'string', 'max:255'],
            'carreras.*.estado'           => ['required_with:carreras', 'in:en_curso,egresado,retirado'],
            'carreras.*.ciclo_actual'     => ['nullable', 'integer', 'min:1', 'max:15'],
            'carreras.*.area'             => ['nullable', 'string', 'max:120'],
            'carreras.*.fecha_inicio'     => ['nullable', 'date'],
            'carreras.*.fecha_fin'        => ['nullable', 'date'],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        $habilidades = $this->input('habilidades');

        if (is_array($habilidades) && count($habilidades) > 15) {
            throw new BusinessException(
                'No puedes seleccionar más de 15 habilidades.',
                400,
                'MAX_HABILIDADES_EXCEDIDO'
            );
        }

        parent::failedValidation($validator);
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre es obligatorio.',
            'apellidos.required' => 'Los apellidos son obligatorios.',

            'dni.required' => 'El DNI es obligatorio.',
            'dni.size' => 'El DNI debe tener exactamente 8 caracteres.',
            'dni.unique' => 'El DNI ya existe en la base de datos.',

            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',

            'nivel_educativo.required' => 'El nivel educativo es obligatorio.',
            'nivel_educativo.in' => 'El nivel educativo no es válido.',

            'disponibilidad.required' => 'La disponibilidad es obligatoria.',
            'disponibilidad.in' => 'La disponibilidad debe ser inmediata, 1_semana o 1_mes.',

            'correo.required' => 'El correo electrónico es obligatorio.',
            'correo.unique' => 'Este correo ya pertenece a una cuenta registrada.',

            'password.required' => 'La contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',

            'celular.required' => 'El número de teléfono es obligatorio.',

            'habilidades.required' => 'Debe seleccionar al menos una habilidad.',
            'habilidades.*.exists' => 'Una o más habilidades seleccionadas no existen o no están disponibles.',
            'habilidades.*.distinct' => 'No puede repetir habilidades.',
            'habilidades.min' => 'Debe seleccionar al menos una habilidad.',
            'habilidades.max' => 'No puedes seleccionar más de 15 habilidades.',

            'carreras.*.carrera_id.exists' => 'La carrera seleccionada no es válida.',
        ];
    }
}
