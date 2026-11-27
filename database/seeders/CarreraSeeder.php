<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\TipoEstudio;
use Illuminate\Database\Seeder;

class CarreraSeeder extends Seeder
{
    public function run(): void
    {
        // Las carreras son globales (las mismas en todos los países) y dependen solo del tipo de estudio.
        // Se agrupan por área solo para ordenar la lista: el área NO se guarda en la carrera.
        // Solo los tipos de TipoEstudio::CON_CARRERA tienen carreras (Secundario y Terciario no).
        // Taxonomía universitaria del frontend (CAREER_TAXONOMY) + Ingeniería Informática.
        $carreras = [
            'Universitario' => [
                'Tecnología' => [
                    'Ingeniería de Software', 'Ciencias de la Computación', 'Ingeniería de Sistemas',
                    'Ingeniería Informática', 'Ingeniería en Telecomunicaciones', 'Ciberseguridad',
                ],
                'Ingeniería' => [
                    'Ingeniería Biomédica', 'Ingeniería Industrial', 'Ingeniería Civil', 'Ingeniería Electrónica',
                    'Ingeniería Mecánica', 'Ingeniería Química', 'Ingeniería Ambiental',
                ],
                'Ciencias de la Salud' => [
                    'Medicina Humana', 'Enfermería', 'Nutrición', 'Tecnología Médica',
                    'Odontología', 'Farmacia y Bioquímica',
                ],
                'Investigación y Datos' => [
                    'Estadística', 'Bioestadística', 'Ciencia de Datos', 'Matemática Aplicada',
                ],
                'Negocios y Administración' => [
                    'Administración', 'Marketing', 'Contabilidad', 'Economía',
                    'Negocios Internacionales', 'Recursos Humanos',
                ],
                'Diseño y Comunicación' => [
                    'Diseño Gráfico', 'Diseño de Producto / UX-UI', 'Comunicación Audiovisual', 'Publicidad y Marketing',
                ],
                'Ciencias Sociales y Educación' => [
                    'Psicología', 'Educación', 'Derecho', 'Sociología',
                ],
            ],
            'Posgrado' => [
                'Tecnología'                    => ['Especialización en Ciberseguridad'],
                'Ingeniería'                    => ['Especialización en Gestión de Proyectos'],
                'Ciencias de la Salud'          => ['Especialización en Salud Pública'],
                'Investigación y Datos'         => ['Especialización en Analítica de Datos'],
                'Negocios y Administración'     => ['Especialización en Finanzas Corporativas'],
                'Diseño y Comunicación'         => ['Especialización en Diseño UX'],
                'Ciencias Sociales y Educación' => ['Especialización en Psicología Clínica'],
            ],
            'Master' => [
                'Tecnología'                    => ['Maestría en Ciencias de la Computación', 'Maestría en Ingeniería de Software'],
                'Ingeniería'                    => ['Maestría en Ingeniería Industrial'],
                'Ciencias de la Salud'          => ['Maestría en Salud Pública'],
                'Investigación y Datos'         => ['Maestría en Ciencia de Datos'],
                'Negocios y Administración'     => ['Maestría en Administración de Empresas (MBA)'],
                'Diseño y Comunicación'         => ['Maestría en Comunicación'],
                'Ciencias Sociales y Educación' => ['Maestría en Educación'],
            ],
            'Doctorado' => [
                'Tecnología'                    => ['Doctorado en Ciencias de la Computación'],
                'Ingeniería'                    => ['Doctorado en Ingeniería'],
                'Ciencias de la Salud'          => ['Doctorado en Ciencias de la Salud'],
                'Investigación y Datos'         => ['Doctorado en Estadística'],
                'Negocios y Administración'     => ['Doctorado en Administración'],
                'Diseño y Comunicación'         => ['Doctorado en Comunicación'],
                'Ciencias Sociales y Educación' => ['Doctorado en Educación'],
            ],
        ];

        $tipos = TipoEstudio::pluck('id', 'nombre');

        foreach ($carreras as $tipo => $carrerasPorArea) {
            foreach ($carrerasPorArea as $nombres) {
                foreach ($nombres as $nombre) {
                    Carrera::firstOrCreate([
                        'nombre'          => $nombre,
                        'tipo_estudio_id' => $tipos[$tipo],
                    ]);
                }
            }
        }
    }
}
