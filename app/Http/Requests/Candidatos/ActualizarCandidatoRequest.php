<?php

namespace App\Http\Requests\Candidatos;

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
            'dni'              => ['sometimes', 'string', 'size:8', Rule::unique('candidatos', 'dni')->ignore($this->user()?->id, 'usuario_id')],
            'fecha_nacimiento' => ['sometimes', 'date'],
            'genero'           => ['sometimes', 'in:masculino,femenino,otro,prefiero no decir'],
            'celular'          => ['sometimes', 'string', 'max:20'],
            'disponibilidad'   => ['sometimes', 'in:inmediata,1_semana,1_mes'],
            'habilidades'      => ['sometimes', 'array'],
            'habilidades.*'    => ['integer', 'exists:habilidades,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'dni.size'               => 'El DNI debe tener exactamente 8 caracteres.',
            'dni.unique'             => 'El DNI ya pertenece a otro candidato registrado.',
            'genero.in'              => 'El género debe ser masculino, femenino, otro o prefiero no decir.',
            'disponibilidad.in'      => 'La disponibilidad debe ser inmediata, 1_semana o 1_mes.',
            'habilidades.*.exists'   => 'Una o más habilidades seleccionadas no son válidas.',
        ];
    }
}