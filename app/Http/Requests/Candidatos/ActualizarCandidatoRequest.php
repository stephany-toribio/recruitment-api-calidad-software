<?php

namespace App\Http\Requests\Candidatos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Exceptions\BusinessException;
use App\Http\Requests\Candidatos\Concerns\ReglasPerfilCandidato;
use Illuminate\Contracts\Validation\Validator;


class ActualizarCandidatoRequest extends FormRequest
{
    use ReglasPerfilCandidato;

    public function authorize(): bool
    {
        return true;
    }

    // Todo es opcional: cada lista que llega (estudios, idiomas, experiencias, habilidades) reemplaza a la guardada
    public function rules(): array
    {
        return array_merge(
            [
                'nombre'           => ['sometimes', 'string', 'max:120'],
                'apellidos'        => ['sometimes', 'string', 'max:255'],
                'dni'              => ['sometimes', 'digits:8', Rule::unique('candidatos', 'dni')->ignore($this->user()?->id, 'usuario_id')],
                'fecha_nacimiento' => ['sometimes', 'date'],
                'genero'           => ['sometimes', 'nullable', 'in:masculino,femenino,otro,prefiero no decir'],
                'celular'          => ['sometimes', 'digits:9'],
            ],
            $this->reglasEstudios(parcial: true),
            $this->reglasIdiomas(parcial: true),
            $this->reglasExperiencias(parcial: true),
            $this->reglasHabilidades(parcial: true, inactivasPermitidas: $this->habilidadesActuales()),
        );
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validarConsistencia($validator),
            fn (Validator $validator) => $this->validarEstadoFinalExperiencias($validator),
            fn (Validator $validator) => $this->validarHabilidadesSegunAreasFinales($validator),
            fn (Validator $validator) => $this->validarMaximoPorTipo(
                $validator,
                $this->has('habilidades') && is_array($this->input('habilidades')) ? $this->input('habilidades') : null
            ),

        ];
    }

    private function habilidadesActuales(): array
    {
        return $this->user()?->candidato?->habilidades()->pluck('habilidades.id')->all() ?? [];
    }

    private ?array $puestosActuales = null;

    private ?array $organizacionesActuales = null;

    private ?array $nivelesActuales = null;

    protected function candidatoActualId(): ?int
    {
        return $this->user()?->candidato?->id;
    }

    // Se consulta una sola vez: lo usan la regla de puesto_id y la validación de puesto_nombre
    protected function puestosActuales(): array
    {
        return $this->puestosActuales ??= $this->user()?->candidato?->experiencias()
            ->pluck('puesto_id')->map(fn ($id) => (int) $id)->unique()->values()->all() ?? [];
    }

    // Igual que los puestos: lo usan la regla de organizacion_id y la validación de organizacion_nombre
    protected function organizacionesActuales(): array
    {
        return $this->organizacionesActuales ??= $this->user()?->candidato?->experiencias()
            ->pluck('organizacion_id')->map(fn ($id) => (int) $id)->unique()->values()->all() ?? [];
    }

    // Igual que los puestos: lo usan la regla de nivel_puesto_id y la validación de nivel_puesto_nombre
    protected function nivelesActuales(): array
    {
        return $this->nivelesActuales ??= $this->user()?->candidato?->experiencias()
            ->pluck('nivel_puesto_id')->map(fn ($id) => (int) $id)->unique()->values()->all() ?? [];
    }

 
    private function validarHabilidadesSegunAreasFinales(Validator $validator): void
    {
        if (! $this->has('habilidades') && ! $this->has('estudios')) {
            return;
        }

        $candidato = $this->user()?->candidato;

        $habilidades = $this->has('habilidades')
            ? (is_array($this->input('habilidades')) ? $this->input('habilidades') : null)
            : $this->habilidadesActuales();

        $areas = $this->has('estudios')
            ? $this->areasDeEstudiosEnviados()
            : $candidato?->estudios()->pluck('area_estudio_id')->map(fn ($id) => (int) $id)->unique()->values()->all();

        $this->validarHabilidadesSegunAreas($validator, $habilidades, $areas, $this->has('habilidades'));
    }

    // Combina lo enviado con lo guardado: sin primer empleo debe quedar al menos una experiencia
    private function validarEstadoFinalExperiencias(Validator $validator): void
    {
        if ($validator->errors()->hasAny(['busca_primer_empleo', 'experiencias'])) {
            return;
        }

        $candidato = $this->user()?->candidato;

        $buscaPrimerEmpleo = $this->has('busca_primer_empleo')
            ? $this->boolean('busca_primer_empleo')
            : (bool) $candidato?->busca_primer_empleo;

        $cantidadExperiencias = $this->has('experiencias')
            ? count((array) $this->input('experiencias'))
            : ($candidato?->experiencias()->count() ?? 0);

        if ($buscaPrimerEmpleo && $this->has('experiencias') && $cantidadExperiencias > 0) {
            $validator->errors()->add('experiencias', 'No puede registrar experiencias si indica que busca su primer empleo.');
        }

        if (! $buscaPrimerEmpleo && $cantidadExperiencias === 0) {
            $validator->errors()->add('experiencias', 'Debe registrar al menos una experiencia laboral o indicar que busca su primer empleo.');
        }
    }

    public function failedValidation(Validator $validator)
    {
        $habilidades = $this->input('habilidades');

        if ($this->excedeMaximoHabilidades || (is_array($habilidades) && count($habilidades) > 30)) {
            throw new BusinessException(
                'No puedes seleccionar más de 15 habilidades técnicas ni más de 15 habilidades blandas.',
                400,
                'MAX_HABILIDADES_EXCEDIDO'
            );
        }

        parent::failedValidation($validator);
    }

    public function messages(): array
    {
        return array_merge([
            'dni.digits'             => 'El DNI debe tener exactamente 8 dígitos.',
            'dni.unique'             => 'El DNI ya pertenece a otro candidato registrado.',
            'genero.in'              => 'El género debe ser masculino, femenino, otro o prefiero no decir.',
            'celular.digits'         => 'El número de teléfono debe tener exactamente 9 dígitos.',
        ], $this->mensajesPerfil());
    }
}
