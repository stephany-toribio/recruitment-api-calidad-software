<?php

namespace Database\Seeders;

use App\Models\Carrera;
use Illuminate\Database\Seeder;

class CarreraSeeder extends Seeder
{
    public function run(): void
    {
        $carreras = [
            'Ingeniería de Sistemas',
            'Ingeniería de Software',
            'Ciencias de la Computación',
            'Ingeniería Informática',
            'Administración y Gestión de Empresas',
            'Marketing y Negocios Digitales',
            'Diseño Gráfico y Multimedia',
            'Contabilidad y Finanzas',
            'Ingeniería Industrial',
            'Recursos Humanos',
        ];

        foreach ($carreras as $nombre) {
            Carrera::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
