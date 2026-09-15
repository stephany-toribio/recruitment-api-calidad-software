<?php

namespace Database\Seeders;

use App\Models\Rol;
use Illuminate\Database\Seeder;

class RolSeeder extends Seeder
{
    /**
     * Inserta los roles base del sistema.
     * Debe ejecutarse siempre primero en DatabaseSeeder.
     */
    public function run(): void
    {
        $roles = ['candidato', 'empresa', 'admin'];

        foreach ($roles as $nombre) {
            Rol::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
