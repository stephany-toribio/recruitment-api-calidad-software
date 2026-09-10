<?php

namespace App\Http\Requests\Candidatos;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

/**
 * FormRequest para RF-04 / RF-05 / RF-07 (HU-01: Registro de Candidato)
 *
 * NOTA PARA EL EQUIPO (Sprint 1):
 * Las reglas 'unique:candidatos,correo' y 'exists:habilidades,id' consultan la BD.
 * Activar o ajustar los nombres exactos de tablas y columnas una vez corridas las migraciones del Sprint 1.
 */
class RegistrarCandidatoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre'        => ['required', 'string', 'max:120'],
            'correo'        => ['required', 'email', 'max:150'], // En Sprint 1: agregar 'unique:candidatos,correo' tras migrar tabla
            'password'      => ['required', 'confirmed', Password::min(8)],
            'telefono'      => ['required', 'string', 'max:20'],
            'habilidades'   => ['required', 'array', 'min:1'],
            'habilidades.*' => ['integer'], // En Sprint 1: agregar 'exists:habilidades,id' tras migrar catálogo
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required'        => 'El nombre completo es obligatorio.',
            'correo.required'        => 'El correo electrónico es obligatorio.',
            'correo.unique'          => 'Este correo ya pertenece a una cuenta registrada.',
            'password.required'      => 'La contraseña es obligatoria.',
            'password.confirmed'     => 'La confirmación de la contraseña no coincide.',
            'telefono.required'      => 'El número de teléfono es obligatorio.',
            'habilidades.required'   => 'Debe seleccionar al menos una habilidad.',
            'habilidades.min'        => 'Debe seleccionar al menos una habilidad.',
            'habilidades.*.exists'   => 'Una o más habilidades seleccionadas no son válidas.',
        ];
    }
}
