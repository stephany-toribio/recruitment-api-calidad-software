<?php

namespace App\Services;

use App\Models\Candidato;
use App\Models\Organizacion;
use App\Models\Puesto;
use App\Services\Auth\UsuarioService;
use Illuminate\Support\Facades\DB;

class CandidatoService
{
    // Relaciones que forman el perfil completo del candidato
    private const RELACIONES = [
        'usuario',
        'habilidades.areaEstudio', // HabilidadResource muestra el área: se carga aquí para evitar N+1
        'estudios.tipoEstudio',
        'estudios.areaEstudio',
        'estudios.pais',
        'estudios.institucion',
        'estudios.carrera',
        'idiomas.idioma',
        'experiencias.puesto',
        'experiencias.organizacion',
        'experiencias.pais',
    ];

    /**
     * Create a new class instance.
     */
    public function __construct(private UsuarioService $usuarioService)
    {
        //
    }

    public function registrar($datos)
    {
        return DB::transaction(function() use($datos){

            //Crea usuario base
            $usuario = $this->usuarioService->registrar($datos, 'candidato');

            //Crear candidato
            $candidato=Candidato::create([
                'usuario_id'           =>$usuario->id,
                'nombre'               =>$datos['nombre'],
                'apellidos'            =>$datos['apellidos'],
                'dni'                  =>$datos['dni'],
                'fecha_nacimiento'     =>$datos['fecha_nacimiento'],
                'genero'               =>$datos['genero'] ?? 'prefiero no decir',
                'busca_primer_empleo'  =>$datos['busca_primer_empleo'],
                'celular'              =>$datos['celular'],
            ]);

            //Asignar habilidades
            if (!empty($datos['habilidades'])) {
                $candidato->habilidades()->sync($datos['habilidades']);
            }

            $this->guardarEstudios($candidato, $datos['estudios']);
            $this->guardarIdiomas($candidato, $datos['idiomas']);

            if (!$candidato->busca_primer_empleo) {
                $this->guardarExperiencias($candidato, $datos['experiencias'] ?? []);
            }

            return $candidato->load(self::RELACIONES);

        });
    }

    //obtener el perfil candidato del usuario autenticado (404 si el usuario no es candidato)
    public function obtenerPorUsuario(int $usuarioId): Candidato
    {
        return Candidato::with(self::RELACIONES)
            ->where('usuario_id', $usuarioId)
            ->firstOrFail();
    }

    //cargar el perfil completo de un candidato ya resuelto por la ruta
    public function cargarPerfil(Candidato $candidato): Candidato
    {
        return $candidato->load(self::RELACIONES);
    }

    //actualizar perfil: cada lista que llega reemplaza a la guardada
    public function actualizar(Candidato $candidato, array $datos):Candidato
    {
        return DB::transaction(function () use($candidato, $datos){
            $candidato->update(array_filter([
                'nombre'               => $datos['nombre']               ?? null,
                'apellidos'            => $datos['apellidos']            ?? null,
                'dni'                  => $datos['dni']                  ?? null,
                'fecha_nacimiento'     => $datos['fecha_nacimiento']     ?? null,
                'genero'               => $datos['genero']               ?? null,
                'celular'              => $datos['celular']              ?? null,
                'busca_primer_empleo'  => $datos['busca_primer_empleo']  ?? null,

            ], fn($v)=>!is_null($v)));

            if (isset($datos['habilidades'])) {
                $candidato->habilidades()->sync($datos['habilidades']);
            }

            if (array_key_exists('estudios', $datos)) {
                $candidato->estudios()->delete();
                $this->guardarEstudios($candidato, $datos['estudios']);
            }

            if (array_key_exists('idiomas', $datos)) {
                $candidato->idiomas()->delete();
                $this->guardarIdiomas($candidato, $datos['idiomas']);
            }

            // Si ahora busca su primer empleo, no puede conservar experiencias
            if ($candidato->busca_primer_empleo) {
                $candidato->experiencias()->delete();
            } elseif (array_key_exists('experiencias', $datos)) {
                $candidato->experiencias()->delete();
                $this->guardarExperiencias($candidato, $datos['experiencias']);
            }

            return $candidato->fresh(self::RELACIONES);

        });

    }

    private function guardarEstudios(Candidato $candidato, array $estudios): void
    {
        foreach ($estudios as $estudio) {
            $candidato->estudios()->create([
                'tipo_estudio_id' => $estudio['tipo_estudio_id'],
                'area_estudio_id' => $estudio['area_estudio_id'],
                'pais_id'         => $estudio['pais_id'],
                'institucion_id'  => $estudio['institucion_id'] ?? null,
                'carrera_id'      => $estudio['carrera_id'] ?? null,
                'estado'          => $estudio['estado'],
                'ciclo_actual'    => $estudio['ciclo_actual'] ?? null,
                'fecha_inicio'    => $estudio['fecha_inicio'],
                'fecha_fin'       => $estudio['fecha_fin'] ?? null,
            ]);
        }
    }

    private function guardarIdiomas(Candidato $candidato, array $idiomas): void
    {
        foreach ($idiomas as $idioma) {
            $candidato->idiomas()->create([
                'idioma_id'     => $idioma['idioma_id'],
                'nivel_escrito' => $idioma['nivel_escrito'],
                'nivel_oral'    => $idioma['nivel_oral'],
            ]);
        }
    }

    private function guardarExperiencias(Candidato $candidato, array $experiencias): void
    {
        foreach ($experiencias as $experiencia) {
            $candidato->experiencias()->create([
                'puesto_id'       => $experiencia['puesto_id'] ?? $this->resolverPuestoEscrito($experiencia['puesto_nombre'], $candidato),
                'organizacion_id' => $experiencia['organizacion_id']
                    ?? $this->resolverOrganizacionEscrita($experiencia['organizacion_nombre'], $experiencia['pais_id'], $candidato),
                'pais_id'         => $experiencia['pais_id'],
                'nivel'           => $experiencia['nivel'],
                'fecha_inicio'    => $experiencia['fecha_inicio'],
                'fecha_fin'       => $experiencia['fecha_fin'] ?? null,
                'descripcion'     => $experiencia['descripcion'] ?? null,
            ]);
        }
    }

    
    /**
     * Puesto escrito por el candidato: si el nombre ya existe en el catálogo global se usa ese;
     * si no, se usa o se crea su propio valor personalizado (no se comparte con otros candidatos).
     * Que no esté deshabilitado ya lo valida ReglasPerfilCandidato.
     */
    private function resolverPuestoEscrito(string $nombre, Candidato $candidato): int
    {
        $nombre = trim($nombre);

        $global = Puesto::whereNull('candidato_id')->where('nombre', $nombre)->first();

        if ($global) {
            return $global->id;
        }

        return Puesto::firstOrCreate(
            ['nombre' => $nombre, 'candidato_id' => $candidato->id],
            ['estado' => 'activo']
        )->id;
    }

    // Igual que el puesto, pero la organización pertenece al país de la experiencia
    private function resolverOrganizacionEscrita(string $nombre, int $paisId, Candidato $candidato): int
    {
        $nombre = trim($nombre);

        $global = Organizacion::whereNull('candidato_id')
            ->where('nombre', $nombre)
            ->where('pais_id', $paisId)
            ->first();

        if ($global) {
            return $global->id;
        }

        return Organizacion::firstOrCreate(
            ['nombre' => $nombre, 'pais_id' => $paisId, 'candidato_id' => $candidato->id],
            ['estado' => 'activo']
        )->id;
    }
};
