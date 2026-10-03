<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $correo = env('ADMIN_CORREO');
        $password = env('ADMIN_PASSWORD');

        // Sin credenciales en el .env no se crea el admin, pero el resto del seed continúa
        if (empty($correo) || empty($password)) {
            $this->command?->warn('AdminSeeder omitido: define ADMIN_CORREO y ADMIN_PASSWORD en el .env.');
            return;
        }

        $rolAdmin = Rol::where('nombre', 'admin')->firstOrFail();

        Usuario::firstOrCreate(
            [
                'correo' => $correo,
            ],
            [
                'password' => $password, // el cast 'hashed' del modelo la encripta
                'rol_id' => $rolAdmin->id,
                'estado_cuenta' => 'activo',
            ]
        );
    }
}
