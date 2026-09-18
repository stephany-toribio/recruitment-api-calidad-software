<?php

namespace App\Http\Requests\Candidatos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;


class RegistrarCandidatoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'           => ['required', 'string', 'max:120'],
            'apellido_paterno' => ['required', 'string', 'max:120'],
            'apellido_materno' => ['required', 'string', 'max:120'],
            'dni'              => ['required', 'string', 'size:8', 'unique:candidatos,dni'], 
            'fecha_nacimiento' => ['required', 'date', 'max:120'], 
            'genero'           => ['required', 'in:masculino,femenino,otro'],
            'correo'           => ['required', 'email', 'max:120', 'unique:usuarios,correo'], 
            'password'         => ['required', 'confirmed', 'max:120'], 
            'celular'          => ['required', 'string', 'max:20'],
            'disponibilidad'   => ['required','in:inmediata,1_semana,1_mes'],
            'habilidades'      => ['required', 'array'],
            'habilidades.*'    => ['required', 'exists:habilidades,id'],
             
        ]; 
    } 

    public function messages(): array
    {
        return [
            'nombre.required'        => 'El nombre completo es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'apellido_materno.required' => 'El apellido materno es obligatorio.',
            'dni.required'           => 'El DNI es obligatorio.',
            'dni.unique'             => 'El DNI ya existe en la base de datos.',
            'fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            'genero.required'        => 'El género es obligatorio.',
            'disponibilidad.required'=> 'La disponibilidad es obligatoria.',
            'disponibilidad.in'      => 'La disponibilidad debe ser inmediata, 1_semana o 1_mes.',
            'correo.required'        => 'El correo electrónico es obligatorio.',
            'correo.unique'          => 'Este correo ya pertenece a una cuenta registrada.',
            'password.required'      => 'La contraseña es obligatoria.',
            'password.confirmed'     => 'La confirmación de la contraseña no coincide.',
            'celular.required'       => 'El número de teléfono es obligatorio.',
            'habilidades.required'   => 'Debe seleccionar al menos una habilidad.',
            'habilidades.min'        => 'Debe seleccionar al menos una habilidad.',
            'habilidades.*.exists'   => 'Una o más habilidades seleccionadas no son válidas.',
        ]; 
    }
}
