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
                'apellido_paterno'     =>$datos['apellido_paterno'],
                'apellido_materno'     =>$datos['apellido_materno'],
                'dni'                  =>$datos['dni'],
                'fecha_nacimiento'     =>$datos['fecha_nacimiento'],
                'genero'               =>$datos['genero'],
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

    //actualizar perfil
    public function actualizar(Candidato $candidato, array $datos):Candidato
    {
        return DB::transaction(function () use($candidato, $datos){
            $candidato->update(array_filter([
                'nombre'               => $datos['nombre']               ?? null,
                'apellido_paterno'     => $datos['apellido_paterno']     ?? null,
                'apellido_materno'     => $datos['apellido_materno']     ?? null,
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