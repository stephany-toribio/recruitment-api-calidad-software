<?php

namespace App\Http\Requests\Candidatos;

use App\Exceptions\BusinessException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActualizarCandidatoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'           => ['sometimes', 'string', 'max:120'],
            'apellidos'        => ['sometimes', 'string', 'max:255'],
            'dni'              => [
                'sometimes',
                'string',
                'size:8',
                Rule::unique('candidatos', 'dni')
                    ->ignore($this->user()?->id, 'usuario_id'),
            ],
            'fecha_nacimiento' => ['sometimes', 'date'],
            'genero'           => [
                'sometimes',
                'nullable',
                'in:masculino,femenino,otro,prefiero no decir',
            ],
            'celular'          => ['sometimes', 'string', 'max:20'],
            'nivel_educativo'  => [
                'sometimes',
                'in:secundaria,tecnico,universitario,posgrado',
            ],
            'disponibilidad'   => [
                'sometimes',
                'in:inmediata,1_semana,1_mes',
            ],

            'habilidades' => [
                'sometimes',
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
                        $habilidadesActuales = $this->habilidadesActuales();

                        $query->where(function (Builder $query) {
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
                        });

                        if (!empty($habilidadesActuales)) {
                            $query->orWhereIn('id', $habilidadesActuales);
                        }
                    }),
            ],

            'carreras'                    => ['sometimes', 'array'],
            'carreras.*.carrera_id'       => [
                'required_with:carreras',
                'integer',
                'exists:carreras,id',
            ],
            'carreras.*.institucion'      => [
                'required_with:carreras',
                'string',
                'max:255',
            ],
            'carreras.*.estado'           => [
                'required_with:carreras',
                'in:en_curso,egresado,retirado',
            ],
            'carreras.*.ciclo_actual'     => [
                'nullable',
                'integer',
                'min:1',
                'max:15',
            ],
            'carreras.*.area'             => [
                'nullable',
                'string',
                'max:120',
            ],
            'carreras.*.fecha_inicio'     => [
                'nullable',
                'date',
            ],
            'carreras.*.fecha_fin'        => [
                'nullable',
                'date',
            ],
        ];
    }

    private function habilidadesActuales(): array
    {
        return $this->user()?->candidato?->habilidades()
            ->pluck('habilidades.id')
            ->all() ?? [];
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
            'dni.size' => 'El DNI debe tener exactamente 8 caracteres.',
            'dni.unique' => 'El DNI ya pertenece a otro candidato registrado.',

            'genero.in' => 'El género debe ser masculino, femenino, otro o prefiero no decir.',

            'disponibilidad.in' => 'La disponibilidad debe ser inmediata, 1_semana o 1_mes.',

            'nivel_educativo.in' => 'El nivel educativo no es válido.',

            'habilidades.*.exists' => 'Una o más habilidades no existen o no están disponibles.',
            'habilidades.min' => 'Debe seleccionar al menos una habilidad.',
            'habilidades.*.distinct' => 'No puede repetir habilidades.',

            'carreras.*.carrera_id.exists' => 'La carrera seleccionada no es válida.',
        ];
    }
}
