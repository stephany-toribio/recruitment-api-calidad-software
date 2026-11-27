<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolSeeder::class,
            AdminSeeder::class,
            // Catálogos base: van antes que las tablas que los referencian
            PaisSeeder::class,
            AreaEstudioSeeder::class,
            TipoEstudioSeeder::class,
            IdiomaSeeder::class,
            InstitucionSeeder::class,   // pais
            PuestoSeeder::class,
            OrganizacionSeeder::class,  // pais
            HabilidadSeeder::class,     // area_estudio
            CarreraSeeder::class,       // area_estudio + pais
            EstadoPostulacionSeeder::class,
            MotivoRechazoSeeder::class,
        ]);
    }
}
