<?php

namespace App\Services;

use App\Models\Candidato;
use App\Services\Auth\UsuarioService;
use Illuminate\Support\Facades\DB;

class CandidatoService
{
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
                'disponibilidad'       =>$datos['disponibilidad'],
                'celular'              =>$datos['celular'],
            ]); 

            //Asignar habilidades
            if (!empty($datos['habilidades'])) {
                $candidato->habilidades()->sync($datos['habilidades']);
            }

            if (!empty($datos['carreras'])) {
                $candidato->carreras()->createMany($datos['carreras']);
            } 

            return $candidato->load('usuario', 'habilidades', 'carreras');
 
        }); 
    }

    //obtener el perfil candidato del usuario autenticado (404 si el usuario no es candidato)
    public function obtenerPorUsuario(int $usuarioId): Candidato
    {
        return Candidato::with(['usuario', 'habilidades', 'carreras'])
            ->where('usuario_id', $usuarioId)
            ->firstOrFail();
    }

    //actualizar perfil
    public function actualizar(Candidato $candidato, array $datos):Candidato
    {
        return DB::transaction(function () use($candidato, $datos){
            $candidato->update(array_filter([
                'nombre'               => $datos['nombre']               ?? null,
                'apellidos'            => $datos['apellidos']            ?? null,
                'dni'                  => $datos['dni']                  ?? null,
                'fecha_nacimiento'     => $datos['fecha_nacimiento']     ?? null,
                'genero'               => $datos['genero']               ?? null,
                'disponibilidad'       => $datos['disponibilidad']       ?? null,
                'celular'              => $datos['celular']              ?? null,
                
            ], fn($v)=>!is_null($v)));

            if (isset($datos['habilidades'])) {
                $candidato->habilidades()->sync($datos['habilidades']);
            }

            return $candidato->fresh(['usuario', 'habilidades', 'carreras']);

        }); 
        
    }  
};       