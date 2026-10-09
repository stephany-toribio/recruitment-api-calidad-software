<?php

namespace Database\Seeders;

use App\Models\Puesto;
use Illuminate\Database\Seeder;

class PuestoSeeder extends Seeder
{
    public function run(): void
    {
        $aprobados = [
            'Desarrollador Backend',
            'Desarrollador Frontend',
            'Desarrollador Full Stack',
            'Analista de Sistemas',
            'Analista de Datos',
            'Ingeniero de Soporte',
            'Ingeniero Industrial',
            'Ingeniero Civil',
            'Asistente Administrativo',
            'Contador',
            'Analista Financiero',
            'Ejecutivo de Ventas',
            'Especialista en Marketing Digital',
            'Diseñador Gráfico',
            'Diseñador UX/UI',
            'Médico General',
            'Enfermero(a)',
            'Psicólogo(a)',
            'Docente',
            'Practicante',
        ];

        // Catálogo global (candidato_id NULL). El default de la tabla es inactivo: se marca activo explícitamente.
        // Los personalizados no se siembran: los crea cada candidato al escribir un puesto que no está en el catálogo.
        foreach ($aprobados as $nombre) {
            Puesto::updateOrCreate(
                ['nombre' => $nombre, 'candidato_id' => null],
                ['estado' => 'activo']
            );
        }
    }
}
