<?php

namespace App\Services\Auth;

use App\Models\Rol;
use App\Models\Usuario;

class UsuarioService
{
    /**
     * Crea un registro base en la tabla `usuarios`.
     *
     * @param array{correo: string, password: string} $datos
     * @param string $rol
     * @return Usuario
     */
    public function registrar(array $datos, string $rol): Usuario
    {
        $rolModelo = Rol::where('nombre', $rol)->firstOrFail();

        return Usuario::create([
            'correo'   => $datos['correo'],
            'password' => $datos['password'],
            'rol_id'   => $rolModelo->id,
        ]);
    }

    /**
     * Actualiza la contraseña y revoca todas las sesiones activas.
     */
    public function actualizarPassword(Usuario $u, string $password): void
    {
        $u->update([
            'password' => $password,
        ]);

        $u->tokens()->delete();
    }
}