<?php

namespace App\Http\Requests\Candidatos\Concerns;

use App\Models\Carrera;
use App\Models\Habilidad;
use App\Models\Institucion;
use App\Models\Organizacion;
use App\Models\TipoEstudio;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;

/**
 * Reglas del perfil del candidato compartidas por RegistrarCandidatoRequest y ActualizarCandidatoRequest.
 */
trait ReglasPerfilCandidato
{
    protected function reglasEstudios(bool $parcial = false): array
    {
        return [
            'estudios'                   => [$parcial ? 'sometimes' : 'required', 'array', 'min:1', 'max:5'],
            'estudios.*.tipo_estudio_id' => ['required', 'integer', 'exists:tipos_estudio,id'],
            'estudios.*.area_estudio_id' => ['required', 'integer', Rule::exists('areas_estudio', 'id')->where('activo', true)],
            'estudios.*.pais_id'         => ['required', 'integer', Rule::exists('paises', 'id')->where('activo', true)],
            'estudios.*.institucion_id'  => ['nullable', 'integer', Rule::exists('instituciones', 'id')->where('estado', 'activo')],
            'estudios.*.carrera_id'      => ['nullable', 'integer', 'exists:carreras,id'],
            'estudios.*.estado'          => ['required', 'in:finalizado,en_curso,incompleto'],
            'estudios.*.ciclo_actual'    => ['nullable', 'integer', 'min:1', 'max:15', 'prohibited_unless:estudios.*.estado,en_curso'],
            'estudios.*.fecha_inicio'    => ['required', 'date_format:Y-m-d'],
            'estudios.*.fecha_fin'       => ['nullable', 'date_format:Y-m-d', 'after_or_equal:estudios.*.fecha_inicio'],
        ];
    }

    protected function reglasIdiomas(bool $parcial = false): array
    {
        return [
            'idiomas'                 => [$parcial ? 'sometimes' : 'required', 'array', 'min:1', 'max:10'],
            'idiomas.*.idioma_id'     => ['required', 'integer', 'distinct', 'exists:idiomas,id'],
            'idiomas.*.nivel_escrito' => ['required', 'in:basico,intermedio,avanzado,nativo'],
            'idiomas.*.nivel_oral'    => ['required', 'in:basico,intermedio,avanzado,nativo'],
        ];
    }

    protected function reglasExperiencias(bool $parcial = false): array
    {
        return [
            'busca_primer_empleo' => [$parcial ? 'sometimes' : 'required', 'boolean'],
            'experiencias'        => [
                $parcial ? 'sometimes' : 'required_if_declined:busca_primer_empleo',
                'prohibited_if_accepted:busca_primer_empleo',
                'array', 'min:1', 'max:5',
            ],
            // Un puesto u organización del catálogo (id) o uno nuevo propuesto por el candidato (nombre)
            'experiencias.*.puesto_id'           => [
                'nullable',
                'required_without:experiencias.*.puesto_nombre',
                'prohibits:experiencias.*.puesto_nombre',
                'integer',
                Rule::exists('puestos', 'id')
                    ->where('estado', 'activo')
                    ->where('estado_catalogo', 'aprobado'),
            ],
            'experiencias.*.puesto_nombre'       => ['nullable', 'required_without:experiencias.*.puesto_id', 'prohibits:experiencias.*.puesto_id', 'string', 'max:200'],
            'experiencias.*.organizacion_id'     => ['nullable', 'required_without:experiencias.*.organizacion_nombre', 'prohibits:experiencias.*.organizacion_nombre', 'integer', Rule::exists('organizaciones', 'id')->where('estado', 'activo')],
            'experiencias.*.organizacion_nombre' => ['nullable', 'string', 'max:200'],
            'experiencias.*.pais_id'             => ['required', 'integer', Rule::exists('paises', 'id')->where('activo', true)],
            'experiencias.*.nivel'               => ['required', 'in:practicante_pre,practicante_pro,trainee,junior,semi_senior,senior,jefe_supervisor,gerente_director'],
            'experiencias.*.fecha_inicio'        => ['required', 'date_format:Y-m-d'],
            'experiencias.*.fecha_fin'           => ['nullable', 'date_format:Y-m-d', 'after_or_equal:experiencias.*.fecha_inicio'],
            'experiencias.*.descripcion'         => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * Seleccionable si está activa y, si es técnica, su área de estudio también lo está.
     */
    protected function reglasHabilidades(bool $parcial = false, array $inactivasPermitidas = []): array
    {
        return [
            'habilidades'   => [$parcial ? 'sometimes' : 'required', 'array', 'min:1', 'max:30'],
            'habilidades.*' => ['required', 'integer', 'distinct', Rule::exists('habilidades', 'id')->where(
                function (Builder $query) use ($inactivasPermitidas) {
                    $query->where(function (Builder $query) {
                        $query->where('activo', true)
                            ->where(function (Builder $query) {
                                $query->where('tipo_habilidad', 'blanda')
                                    ->orWhere(function (Builder $query) {
                                        $query->where('tipo_habilidad', 'tecnica')
                                            ->whereExists(function (Builder $query) {
                                                $query->selectRaw('1')
                                                    ->from('areas_estudio')
                                                    ->whereColumn('areas_estudio.id', 'habilidades.area_estudio_id')
                                                    ->where('areas_estudio.activo', true);
                                            });
                                    });
                            });
                    });

                    if (! empty($inactivasPermitidas)) {
                        $query->orWhereIn('id', $inactivasPermitidas);
                    }
                }
            )],
        ];
    }

    /**
     * Reglas que cruzan tablas. Repiten lo que garantizan las FK compuestas para responder 422 con el campo exacto
     * en vez de un 500 de la BD. Solo revisan los datos que llegaron (con Precognition cada paso envía una parte).
     */
    protected function validarConsistencia(Validator $validator): void
    {
        $this->validarEstudiosContraCatalogo($validator);
        $this->validarExperienciasContraCatalogo($validator);
    }

    private function validarEstudiosContraCatalogo(Validator $validator): void
    {
        $estudios = $this->input('estudios');

        if (! is_array($estudios)) {
            return;
        }

       
        $tipos = TipoEstudio::whereIn('id', $this->idsDe($estudios, 'tipo_estudio_id'))->pluck('nombre', 'id');
        $carreras = Carrera::whereIn('id', $this->idsDe($estudios, 'carrera_id'))->get()->keyBy('id');
        $paisesInstitucion = Institucion::whereIn('id', $this->idsDe($estudios, 'institucion_id'))->pluck('pais_id', 'id');
        $estudiosVistos = [];

        foreach ($estudios as $i => $estudio) {
            if (! is_array($estudio)) {
                continue;
            }

            $tipoId = $this->entero($estudio['tipo_estudio_id'] ?? null);
            $tipo = $tipoId ? ($tipos[$tipoId] ?? null) : null;

            if ($tipo === null) {
                continue; // el tipo inexistente ya lo reporta rules()
            }

            $carreraId = $this->entero($estudio['carrera_id'] ?? null);
            $institucionId = $this->entero($estudio['institucion_id'] ?? null);

            if (in_array($tipo, TipoEstudio::CON_CARRERA, true)) {
                if (blank($estudio['institucion_id'] ?? null)) {
                    $this->agregarError($validator, "estudios.$i.institucion_id", "La institución es obligatoria para estudios de tipo {$tipo}.");
                }
                if (blank($estudio['carrera_id'] ?? null)) {
                    $this->agregarError($validator, "estudios.$i.carrera_id", "La carrera es obligatoria para estudios de tipo {$tipo}.");
                }
            }

            if (in_array($tipo, TipoEstudio::SIN_INSTITUCION, true)) {
                if (filled($estudio['institucion_id'] ?? null)) {
                    $this->agregarError($validator, "estudios.$i.institucion_id", "Los estudios de tipo {$tipo} no llevan institución.");
                }
                if (filled($estudio['carrera_id'] ?? null)) {
                    $this->agregarError($validator, "estudios.$i.carrera_id", "Los estudios de tipo {$tipo} no llevan carrera.");
                }
            }

            $carrera = $carreraId ? $carreras->get($carreraId) : null;

            if ($carrera && (
                (int) $carrera->tipo_estudio_id !== $tipoId
                || (int) $carrera->area_estudio_id !== $this->entero($estudio['area_estudio_id'] ?? null)
                || (int) $carrera->pais_id !== $this->entero($estudio['pais_id'] ?? null)
            )) {
                $this->agregarError($validator, "estudios.$i.carrera_id", 'La carrera seleccionada no corresponde al tipo de estudio, área o país indicados.');
            }

            $paisInstitucion = $institucionId ? $this->entero($paisesInstitucion[$institucionId] ?? null) : null;

            if ($paisInstitucion !== null && $paisInstitucion !== $this->entero($estudio['pais_id'] ?? null)) {
                $this->agregarError($validator, "estudios.$i.institucion_id", 'La institución seleccionada no pertenece al país indicado.');
            }

            // Mismo criterio que el unique candidato_estudio_unique de la BD
            if ($carreraId) {
                $clave = "$tipoId-$institucionId-$carreraId";

                if (isset($estudiosVistos[$clave])) {
                    $this->agregarError($validator, "estudios.$i.carrera_id", 'Este estudio ya fue registrado (mismo tipo, institución y carrera).');
                }

                $estudiosVistos[$clave] = true;
            }
        }
    }

    private function validarExperienciasContraCatalogo(Validator $validator): void
    {
        $experiencias = $this->input('experiencias');

        if (! is_array($experiencias)) {
            return;
        }

        $paisesOrganizacion = Organizacion::whereIn('id', $this->idsDe($experiencias, 'organizacion_id'))->pluck('pais_id', 'id');

        foreach ($experiencias as $i => $experiencia) {
            if (! is_array($experiencia)) {
                continue;
            }

            $organizacionId = $this->entero($experiencia['organizacion_id'] ?? null);
            $paisOrganizacion = $organizacionId ? $this->entero($paisesOrganizacion[$organizacionId] ?? null) : null;

            if ($paisOrganizacion !== null && $paisOrganizacion !== $this->entero($experiencia['pais_id'] ?? null)) {
                $this->agregarError($validator, "experiencias.$i.organizacion_id", 'La organización seleccionada no pertenece al país de la experiencia.');
            }
        }
    }

    /**
     * Regla C: cada habilidad técnica debe pertenecer a un área de los estudios del candidato (las blandas no tienen área).
     * Si faltan las habilidades o las áreas (p. ej. un paso de Precognition sin estudios en el body) no se valida aquí;
     * el registro final siempre trae ambos.
     *
     * @param array|null $habilidades ids de las habilidades finales
     * @param array|null $areas       ids de las áreas de los estudios finales
     * @param bool       $enviadas    true: llegaron en la petición (error por índice); false: son las ya guardadas
     */
    protected function validarHabilidadesSegunAreas(Validator $validator, ?array $habilidades, ?array $areas, bool $enviadas = true): void
    {
        if ($habilidades === null || $areas === null || $validator->errors()->has('habilidades')) {
            return;
        }

        $areasPorTecnica = Habilidad::whereIn('id', collect($habilidades)->map(fn ($id) => $this->entero($id))->filter()->all())
            ->where('tipo_habilidad', 'tecnica')
            ->pluck('area_estudio_id', 'id');

        foreach ($habilidades as $i => $id) {
            $id = $this->entero($id);

            // Blandas o inexistentes: no aplica (las inexistentes ya las reporta rules())
            if ($id === null || ! $areasPorTecnica->has($id) || in_array((int) $areasPorTecnica[$id], $areas, true)) {
                continue;
            }

            if (! $enviadas) {
                $this->agregarError($validator, 'habilidades', 'Tus habilidades técnicas actuales no corresponden a las áreas de los nuevos estudios. Actualiza también tus habilidades.');
                return;
            }

            $this->agregarError($validator, "habilidades.$i", 'La habilidad técnica seleccionada no corresponde a ninguna de las áreas de tus estudios.');
        }
    }

    // Áreas de los estudios que llegaron en la petición, o null si no llegaron estudios
    protected function areasDeEstudiosEnviados(): ?array
    {
        $estudios = $this->input('estudios');

        return is_array($estudios) ? $this->idsDe($estudios, 'area_estudio_id') : null;
    }

    // No duplica el error si rules() ya reportó ese campo
    private function agregarError(Validator $validator, string $campo, string $mensaje): void
    {
        if (! $validator->errors()->has($campo)) {
            $validator->errors()->add($campo, $mensaje);
        }
    }

    private function idsDe(array $items, string $campo): array
    {
        return collect($items)
            ->map(fn ($item) => is_array($item) ? $this->entero($item[$campo] ?? null) : null)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function entero(mixed $valor): ?int
    {
        return is_numeric($valor) ? (int) $valor : null;
    }

        /** true si en esta petición se superó el máximo por tipo (lo usa failedValidation para responder 400) */
    protected bool $excedeMaximoHabilidades = false;

    /**
     * Máximo 15 habilidades técnicas y 15 blandas. La lista llega mezclada, así que se consulta el tipo de cada id.
     */
    protected function validarMaximoPorTipo(Validator $validator, ?array $habilidades): void
    {
        if ($habilidades === null) {
            return;
        }

        $ids = collect($habilidades)->map(fn ($id) => $this->entero($id))->filter()->unique()->all();
        $porTipo = Habilidad::whereIn('id', $ids)->pluck('tipo_habilidad')->countBy();

        if (($porTipo['tecnica'] ?? 0) > 15 || ($porTipo['blanda'] ?? 0) > 15) {
            $this->excedeMaximoHabilidades = true;
            $this->agregarError($validator, 'habilidades', 'No puedes seleccionar más de 15 habilidades técnicas ni más de 15 habilidades blandas.');
        }
    }


    protected function mensajesPerfil(): array
    {
        return [
            'estudios.required'                  => 'Debe registrar al menos un estudio.',
            'estudios.min'                       => 'Debe registrar al menos un estudio.',
            'estudios.max'                       => 'No puede registrar más de 5 estudios.',
            'estudios.*.tipo_estudio_id.required' => 'El tipo de estudio es obligatorio.',
            'estudios.*.tipo_estudio_id.exists'  => 'El tipo de estudio no es válido.',
            'estudios.*.area_estudio_id.required' => 'El área de estudio es obligatoria.',
            'estudios.*.area_estudio_id.exists'  => 'El área de estudio no existe o está inactiva.',
            'estudios.*.pais_id.required'        => 'El país del estudio es obligatorio.',
            'estudios.*.pais_id.exists'          => 'El país del estudio no existe o está inactivo.',
            'estudios.*.institucion_id.exists'   => 'La institución no existe o está inactiva.',
            'estudios.*.carrera_id.exists'       => 'La carrera seleccionada no es válida.',
            'estudios.*.estado.required'         => 'El estado del estudio es obligatorio.',
            'estudios.*.estado.in'               => 'El estado del estudio debe ser finalizado, en_curso o incompleto.',
            'estudios.*.ciclo_actual.prohibited_unless' => 'El ciclo actual solo se indica en estudios en curso.',
            'estudios.*.fecha_inicio.required'   => 'La fecha de inicio del estudio es obligatoria.',
            'estudios.*.fecha_inicio.date_format' => 'La fecha de inicio debe tener el formato AAAA-MM-DD.',
            'estudios.*.fecha_fin.date_format'   => 'La fecha de fin debe tener el formato AAAA-MM-DD.',
            'estudios.*.fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',

            'idiomas.required'                   => 'Debe registrar al menos un idioma.',
            'idiomas.min'                        => 'Debe registrar al menos un idioma.',
            'idiomas.max'                        => 'No puede registrar más de 10 idiomas.',
            'idiomas.*.idioma_id.required'       => 'El idioma es obligatorio.',
            'idiomas.*.idioma_id.distinct'       => 'No puede repetir idiomas.',
            'idiomas.*.idioma_id.exists'         => 'El idioma seleccionado no es válido.',
            'idiomas.*.nivel_escrito.required'   => 'El nivel escrito es obligatorio.',
            'idiomas.*.nivel_escrito.in'         => 'El nivel escrito debe ser basico, intermedio, avanzado o nativo.',
            'idiomas.*.nivel_oral.required'      => 'El nivel oral es obligatorio.',
            'idiomas.*.nivel_oral.in'            => 'El nivel oral debe ser basico, intermedio, avanzado o nativo.',

            'busca_primer_empleo.required'       => 'Indique si busca su primer empleo.',
            'busca_primer_empleo.boolean'        => 'El campo busca_primer_empleo debe ser verdadero o falso.',
            'experiencias.required_if_declined'  => 'Debe registrar al menos una experiencia laboral o indicar que busca su primer empleo.',
            'experiencias.prohibited_if_accepted' => 'No puede registrar experiencias si indica que busca su primer empleo.',
            'experiencias.min'                   => 'Debe registrar al menos una experiencia laboral.',
            'experiencias.max'                   => 'No puede registrar más de 5 experiencias laborales.',
            'experiencias.*.puesto_id.required_without'       => 'Seleccione un puesto o escriba uno nuevo.',
            'experiencias.*.puesto_id.prohibits'              => 'Envíe el puesto del catálogo o uno nuevo, no ambos.',
            'experiencias.*.puesto_id.exists'                 => 'El puesto seleccionado no es válido.',
            'experiencias.*.puesto_nombre.required_without'   => 'Seleccione un puesto o escriba uno nuevo.',
            'experiencias.*.puesto_nombre.prohibits'          => 'Envíe el puesto del catálogo o uno nuevo, no ambos.',
            'experiencias.*.puesto_nombre.string'             => 'El nombre del puesto debe ser texto.',
            'experiencias.*.puesto_nombre.max'                => 'El nombre del puesto no puede superar los 200 caracteres.',
            'experiencias.*.organizacion_id.required_without' => 'Seleccione una organización o escriba una nueva.',
            'experiencias.*.organizacion_id.prohibits'        => 'Envíe la organización del catálogo o una nueva, no ambas.',
            'experiencias.*.organizacion_id.exists'           => 'La organización seleccionada no es válida.',
            'experiencias.*.pais_id.required'    => 'El país de la experiencia es obligatorio.',
            'experiencias.*.pais_id.exists'      => 'El país de la experiencia no existe o está inactivo.',
            'experiencias.*.nivel.required'      => 'El nivel del puesto es obligatorio.',
            'experiencias.*.nivel.in'            => 'El nivel del puesto no es válido.',
            'experiencias.*.fecha_inicio.required' => 'La fecha de inicio de la experiencia es obligatoria.',
            'experiencias.*.fecha_inicio.date_format' => 'La fecha de inicio debe tener el formato AAAA-MM-DD.',
            'experiencias.*.fecha_fin.date_format' => 'La fecha de fin debe tener el formato AAAA-MM-DD.',
            'experiencias.*.fecha_fin.after_or_equal' => 'La fecha de fin no puede ser anterior a la de inicio.',

            'habilidades.required'               => 'Debe seleccionar al menos una habilidad.',
            'habilidades.min'                    => 'Debe seleccionar al menos una habilidad.',
            'habilidades.max'                    => 'No puedes seleccionar más de 30 habilidades (15 técnicas y 15 blandas).',
            'habilidades.*.distinct'             => 'No puede repetir habilidades.',
            'habilidades.*.exists'               => 'Una o más habilidades seleccionadas no existen o no están disponibles.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $experiencias = $this->input('experiencias');

        if (! is_array($experiencias)) {
            return;
        }

        foreach ($experiencias as &$experiencia) {
            if (! is_array($experiencia)) {
                continue;
            }

            if (
                array_key_exists('puesto_nombre', $experiencia)
                && is_string($experiencia['puesto_nombre'])
            ) {
                $experiencia['puesto_nombre'] = preg_replace(
                    '/\s+/u',
                    ' ',
                    trim($experiencia['puesto_nombre'])
                );
            }
        }

        unset($experiencia);

        $this->merge([
            'experiencias' => $experiencias,
        ]);
    }
}
