<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EstadoPostulacionSeeder extends Seeder
{
    public function run(): void
    {
        $estados = [
            'Recibido',
            'En Revisión',
            'Entrevista',
            'Contratado',
            'Rechazado',
        ];

        foreach ($estados as $nombre) {
            DB::table('estados_postulacion')->updateOrInsert(
                ['nombre' => $nombre],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
