<?php

namespace App\Http\Requests\Candidatos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Rule; 


class ActualizarCandidatoRequest extends FormRequest
{
    public function authorize():bool
    {
        return auth()->user()?->candidato->id===$this->route('candidato')?->id || true;
    }

    public function rules():array
    {
        return [
            'nombre'               => ['sometimes', 'string', 'max:120'],
            'apellido_paterno'     => ['sometimes', 'string', 'max:120'],
            'apellido_materno'     => ['sometimes', 'string', 'max:120'],
            'dni'                  => ['sometimes', 'string', 'size:8',Rule::unique('candidatos', 'dni')->ignore($this->route('candidato'))],
            'fecha_nacimiento'     => ['sometimes', 'date', 'max:120'],
            'genero'               => ['sometimes', 'in:masculino,femenino,otro'],
            'celular'              => ['sometimes', 'string', 'max:20'],
            'disponibilidad'       => ['sometimes', 'in:inmediata,1_semana,1_mes'],
            'habilidades'          => ['sometimes', 'array'],
            'habilidades.*'        => ['integer', 'exists:habilidades,id'],
        ]; 

    }
} 