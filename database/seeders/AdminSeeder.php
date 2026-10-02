<?php

namespace Database\Seeders;

use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $rolAdmin = Rol::where('nombre', 'admin')->firstOrFail();

        Usuario::firstOrCreate(
            [
                'correo' => env('ADMIN_CORREO'),
            ],
            [
                'password' => Hash::make(env('ADMIN_PASSWORD')),
                'rol_id' => $rolAdmin->id,
                'estado_cuenta' => 'activo',
            ]
        );
    }
}