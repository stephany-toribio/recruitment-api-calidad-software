<?php

namespace Database\Seeders;

use App\Models\Habilidad;
use Illuminate\Database\Seeder;

class HabilidadSeeder extends Seeder
{
    public function run(): void
    {
        $habilidades = [
            ['nombre' => 'PHP', 'tipo_habilidad' => 'tecnica'],
            ['nombre' => 'Laravel', 'tipo_habilidad' => 'tecnica'],
            ['nombre' => 'Python', 'tipo_habilidad' => 'tecnica'],
            ['nombre' => 'Node.js', 'tipo_habilidad' => 'tecnica'],
            ['nombre' => 'JavaScript', 'tipo_habilidad' => 'tecnica'],
            ['nombre' => 'React', 'tipo_habilidad' => 'tecnica'],
            ['nombre' => 'Angular', 'tipo_habilidad' => 'tecnica'],
            ['nombre' => 'Vue.js', 'tipo_habilidad' => 'tecnica'],
            ['nombre' => 'MySQL', 'tipo_habilidad' => 'tecnica'],
            ['nombre' => 'PostgreSQL', 'tipo_habilidad' => 'tecnica'],
            ['nombre' => 'Docker', 'tipo_habilidad' => 'tecnica'],
            ['nombre' => 'Git', 'tipo_habilidad' => 'tecnica'],
            ['nombre' => 'Excel', 'tipo_habilidad' => 'tecnica'],
            ['nombre' => 'Trabajo en equipo', 'tipo_habilidad' => 'blanda'],
            ['nombre' => 'Comunicación efectiva', 'tipo_habilidad' => 'blanda'],
            ['nombre' => 'Liderazgo', 'tipo_habilidad' => 'blanda'],
        ];

        foreach ($habilidades as $habilidad) {
            Habilidad::updateOrCreate(
                ['nombre' => $habilidad['nombre']],
                [
                    'tipo_habilidad' => $habilidad['tipo_habilidad'],
                    'estado' => 'activo',
                ]
            );
        }
    }
}
