<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MotivoRechazoSeeder extends Seeder
{
    public function run(): void
    {
        $motivos = [
            'Perfil no se ajusta',
            'Expectativa salarial alta',
            'Proceso cerrado',
        ];

        foreach ($motivos as $motivo) {
            DB::table('motivos_rechazo')->updateOrInsert(
                ['motivo' => $motivo],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
