<?php

namespace App\Http\Requests\Candidatos;

use Illuminate\Foundation\Http\FormRequest;
use App\Exceptions\BusinessException;
use App\Http\Requests\Candidatos\Concerns\ReglasPerfilCandidato;
use App\Rules\PasswordSegura;
use Illuminate\Contracts\Validation\Validator;


// Con Precognition (header "Precognition: true") el frontend valida cada paso del registro
// enviando en "Precognition-Validate-Only" solo los campos de ese paso.
class RegistrarCandidatoRequest extends FormRequest
{
    use ReglasPerfilCandidato;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return array_merge(
            [
                'nombre'           => ['required', 'string', 'max:120'],
                'apellidos'        => ['required', 'string', 'max:255'],
                'dni'              => ['required', 'digits:8', 'unique:candidatos,dni'],
                'fecha_nacimiento' => ['required', 'date'],
                'genero'           => ['nullable', 'in:masculino,femenino,otro,prefiero no decir'],
                'correo'           => ['required', 'email', 'max:120', 'unique:usuarios,correo'],
                'password'         => ['required', 'confirmed', new PasswordSegura(), 'max:120'],
                'celular'          => ['required', 'digits:9'],
            ],
            $this->reglasEstudios(),
            $this->reglasIdiomas(),
            $this->reglasExperiencias(),
            $this->reglasHabilidades(),
        );
    }

    public function after(): array
    {
        return [
            fn (Validator $validator) => $this->validarConsistencia($validator),
        ];
    }

    public function failedValidation(Validator $validator)
    {
        $habilidades = $this->input('habilidades');

        if (is_array($habilidades) && count($habilidades)>15)
        {
            throw new BusinessException(
                'No puedes seleccionar mas de 15 habilidades',
                400,
                'MAX_HABILIDADES_EXCEDIDO'
            );
        }

        parent::failedValidation($validator);
    }

    public function messages(): array
    {
        return array_merge([
            'nombre.required'             => 'El nombre es obligatorio.',
            'apellidos.required'          => 'Los apellidos son obligatorios.',
            'dni.required'                => 'El DNI es obligatorio.',
            'dni.digits'                  => 'El DNI debe tener exactamente 8 dígitos.',
            'dni.unique'                  => 'El DNI ya existe en la base de datos.',

            'fecha_nacimiento.required'   => 'La fecha de nacimiento es obligatoria.',
            'genero.in'                   => 'El género debe ser masculino, femenino, otro o prefiero no decir.',

            'correo.required'             => 'El correo electrónico es obligatorio.',
            'correo.unique'               => 'Este correo ya pertenece a una cuenta registrada.',
            'password.required'           => 'La contraseña es obligatoria.',
            'password.confirmed'          => 'La confirmación de la contraseña no coincide.',

            'celular.required'            => 'El número de teléfono es obligatorio.',
            'celular.digits'              => 'El número de teléfono debe tener exactamente 9 dígitos.',
        ], $this->mensajesPerfil());
    }
}
