<?php

namespace Database\Seeders;

use App\Models\Habilidad;
use Illuminate\Database\Seeder;

class HabilidadSeeder extends Seeder
{
    /**
     * Inserta el catálogo inicial de habilidades.
     * Usa firstOrCreate para ser idempotente.
     */
    public function run(): void
    {
        $habilidades = [
            ['nombre' => 'PHP',        'categoria' => 'Tecnica'],
            ['nombre' => 'Laravel',    'categoria' => 'Tecnica'],
            ['nombre' => 'Python',     'categoria' => 'Tecnica'],
            ['nombre' => 'Node.js',    'categoria' => 'Tecnica'],
            ['nombre' => 'JavaScript', 'categoria' => 'Tecnica'],
            ['nombre' => 'React',      'categoria' => 'Tecnica'],
            ['nombre' => 'Angular',    'categoria' => 'Tecnica'],
            ['nombre' => 'Vue.js',     'categoria' => 'Tecnica'],
            ['nombre' => 'MySQL',      'categoria' => 'Tecnica'],
            ['nombre' => 'PostgreSQL', 'categoria' => 'Tecnica'],
            ['nombre' => 'Docker',     'categoria' => 'Tecnica'],
            ['nombre' => 'Git',        'categoria' => 'Tecnica'],
            ['nombre' => 'Excel',      'categoria' => 'Blanda'],
            ['nombre' => 'Trabajo en equipo', 'categoria' => 'Blanda'],
            ['nombre' => 'Comunicacion efectiva', 'categoria' => 'Blanda'],
            ['nombre' => 'Liderazgo', 'categoria' => 'Blanda'],
        ];

        foreach ($habilidades as $habilidad) {
            Habilidad::firstOrCreate(
                ['nombre' => $habilidad['nombre']],
                $habilidad
            );
        }
    }
}
