<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     * Orden de ejecución es importante: RolSeeder siempre primero.
     */
    public function run(): void
    {
        $this->call([
            RolSeeder::class,       // Primero: usuarios dependen de roles
            HabilidadSeeder::class, // Segundo: catálogo de habilidades
        ]);
    }
}
