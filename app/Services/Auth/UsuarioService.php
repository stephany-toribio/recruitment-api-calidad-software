<?php

namespace App\Services\Auth;

use App\Models\Rol;
use App\Models\Usuario;

class UsuarioService
{
    /**
     * Crea un registro base en la tabla `usuarios`.
     * Este método es el único punto de escritura en `usuarios` para módulos externos.
     *
     * @param array{correo: string, password: string} $datos
     * @param string $rol — 'candidato' | 'empresa'
     * @return Usuario
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException si el rol no existe
     * @throws \Illuminate\Database\QueryException si el correo ya existe
     */
    public function registrar(array $datos, string $rol): Usuario
    {
        $rolModelo = Rol::where('nombre', $rol)->firstOrFail();

        return Usuario::create([
            'correo'   => $datos['correo'],
            'password' => $datos['password'], // el cast 'hashed' en el modelo lo encripta
            'rol_id'   => $rolModelo->id,
        ]);
    }
}
