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

        // Los defaults de la tabla son inactivo/pendiente: el catálogo base se marca explícitamente
        foreach ($aprobados as $nombre) {
            Puesto::updateOrCreate(
                ['nombre' => $nombre],
                ['estado' => 'activo', 'estado_catalogo' => 'aprobado']
            );
        }

        // Ejemplo de puesto propuesto por un candidato, para probar la moderación del admin
        Puesto::updateOrCreate(
            ['nombre' => 'Analista de Growth Marketing'],
            ['estado' => 'inactivo', 'estado_catalogo' => 'pendiente']
        );
    }
}
