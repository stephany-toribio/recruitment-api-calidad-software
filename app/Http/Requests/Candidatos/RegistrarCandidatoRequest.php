<?php

namespace App\Http\Requests\Candidatos;

use Illuminate\Foundation\Http\FormRequest;
use App\Exceptions\BusinessException;
use App\Http\Requests\Candidatos\Concerns\ReglasPerfilCandidato;
use App\Models\Candidato;
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
                // Al menos EDAD_MINIMA años cumplidos a la fecha de hoy
                'fecha_nacimiento' => ['required', 'date', 'before_or_equal:' . now()->subYears(Candidato::EDAD_MINIMA)->toDateString()],
                'genero'           => ['nullable', 'in:masculino,femenino,otro,prefiero no decir'],
                'correo'           => ['required', 'email', 'max:120', 'unique:usuarios,correo'],
                'password'         => ['required', 'confirmed', new PasswordSegura(), 'max:120'],
                'celular'          => ['required', 'digits:9', 'unique:candidatos,celular'],
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
            fn (Validator $validator) => $this->validarHabilidadesSegunAreas(
                $validator,
                is_array($this->input('habilidades')) ? $this->input('habilidades') : null,
                $this->areasDeEstudiosEnviados()
            ),

            fn (Validator $validator) => $this->validarMaximoPorTipo(
                $validator,
                is_array($this->input('habilidades')) ? $this->input('habilidades') : null
            ),

        ];
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
            'nombre.required'             => 'El nombre es obligatorio.',
            'apellidos.required'          => 'Los apellidos son obligatorios.',
            'dni.required'                => 'El DNI es obligatorio.',
            'dni.digits'                  => 'El DNI debe tener exactamente 8 dígitos.',
            'dni.unique'                  => 'El DNI ya existe en la base de datos.',

            'fecha_nacimiento.required'   => 'La fecha de nacimiento es obligatoria.',
            'fecha_nacimiento.date'       => 'La fecha de nacimiento no es válida.',
            'fecha_nacimiento.before_or_equal' => 'Debes tener al menos ' . Candidato::EDAD_MINIMA . ' años para registrarte.',
            'genero.in'                   => 'El género debe ser masculino, femenino, otro o prefiero no decir.',

            'correo.required'             => 'El correo electrónico es obligatorio.',
            'correo.unique'               => 'Este correo ya pertenece a una cuenta registrada.',
            'password.required'           => 'La contraseña es obligatoria.',
            'password.confirmed'          => 'La confirmación de la contraseña no coincide.',

            'celular.required'            => 'El número de teléfono es obligatorio.',
            'celular.digits'              => 'El número de teléfono debe tener exactamente 9 dígitos.',
            'celular.unique'              => 'El número de teléfono ya está registrado.',
        ], $this->mensajesPerfil());
    }
}
