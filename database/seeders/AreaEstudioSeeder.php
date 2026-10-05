<?php

namespace Database\Seeders;

use App\Models\AreaEstudio;
use Illuminate\Database\Seeder;

class AreaEstudioSeeder extends Seeder
{
    public function run(): void
    {
        // Mismas categorías que CAREER_TAXONOMY del frontend
        $areas = [
            'Tecnología',
            'Ingeniería',
            'Ciencias de la Salud',
            'Investigación y Datos',
            'Negocios y Administración',
            'Diseño y Comunicación',
            'Ciencias Sociales y Educación',
        ];

        foreach ($areas as $nombre) {
            AreaEstudio::updateOrCreate(['nombre' => $nombre], ['activo' => true]);
        }
    }
}
