<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use RuntimeException;

class QaCargaSeeder extends Seeder
{
    public function run(): void
    {
        if (
            ! app()->environment('local')
            || DB::selectOne('SELECT DATABASE() AS nombre')->nombre !== 'portal_empleos_calidad_qa'
        ) {
            throw new RuntimeException('QaCargaSeeder solo corre en la base local aislada de QA.');
        }

        $crear = new ReflectionMethod(QaSeeder::class, 'crearCandidato');
        $crear->setAccessible(true);
        $qa = new QaSeeder();
        $n = 0;

        DB::transaction(function () use ($crear, $qa, &$n): void {
            foreach (['lectura', 'login', 'postulacion'] as $perfil) {
                for ($i = 1; $i <= 50; $i++) {
                    $n++;
                    $crear->invoke(
                        $qa,
                        sprintf('%s%03d@carga.qa.invalid', $perfil, $i),
                        'Carga',
                        ucfirst($perfil).' '.$i,
                        sprintf('7%07d', $n),
                        sprintf('98%07d', $n),
                        'Qa-Carga#2026'
                    );
                }
            }
        });

        $this->command?->info("$n candidatos de carga creados.");
    }
}