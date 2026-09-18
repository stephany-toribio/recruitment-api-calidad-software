<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            HabilidadSeeder::class,
            EstadoPostulacionSeeder::class,
            MotivoRechazoSeeder::class,
        ]);
    }
}
