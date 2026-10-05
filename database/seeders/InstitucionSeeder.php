<?php

namespace Database\Seeders;

use App\Models\Institucion;
use App\Models\Pais;
use Illuminate\Database\Seeder;

class InstitucionSeeder extends Seeder
{
    public function run(): void
    {
        // Solo universidades: Secundario y Terciario no llevan institución.
        // Cada país necesita al menos una para Universitario, Posgrado, Master y Doctorado.
        $institucionesPorPais = [
            'Perú' => [
                'Universidad Nacional Mayor de San Marcos',
                'Universidad Nacional de Ingeniería',
                'Pontificia Universidad Católica del Perú',
                'Universidad de Lima',
                'Universidad Peruana de Ciencias Aplicadas',
                'Universidad San Ignacio de Loyola',
                'Universidad Tecnológica del Perú',
                'Universidad Peruana Cayetano Heredia',
                'Universidad del Pacífico',
                'Universidad Nacional Agraria La Molina',
            ],
            'Argentina' => [
                'Universidad de Buenos Aires',
                'Universidad Nacional de Córdoba',
                'Universidad Tecnológica Nacional',
            ],
            'Chile' => [
                'Universidad de Chile',
                'Pontificia Universidad Católica de Chile',
                'Universidad de Santiago de Chile',
            ],
            'Colombia' => [
                'Universidad Nacional de Colombia',
                'Universidad de los Andes',
                'Pontificia Universidad Javeriana',
            ],
            'México' => [
                'Universidad Nacional Autónoma de México',
                'Universidad Autónoma Metropolitana',
                'Universidad de Guadalajara',
            ],
        ];

        $paises = Pais::pluck('id', 'nombre');

        foreach ($institucionesPorPais as $pais => $instituciones) {
            foreach ($instituciones as $nombre) {
                Institucion::updateOrCreate(
                    ['nombre' => $nombre, 'pais_id' => $paises[$pais]],
                    ['estado' => 'activo']
                );
            }
        }
    }
}
