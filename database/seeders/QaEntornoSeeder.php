<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class QaEntornoSeeder extends Seeder
{
    public function run(): void
    {
        if (
            app()->configurationIsCached()
            || ! app()->environment('local')
            || DB::connection()->getDriverName() !== 'mysql'
            || DB::connection()->getConfig('host') !== 'mysql'
            || DB::selectOne('SELECT DATABASE() AS nombre')->nombre
                !== 'portal_empleos_calidad_qa'
        ) {
            throw new RuntimeException(
                'QaEntornoSeeder requiere la base local aislada de QA y configuracion sin cache.'
            );
        }

        if (strlen((string) env('QA_FIXTURE_PASSWORD', '')) < 12) {
            throw new RuntimeException(
                'Define QA_FIXTURE_PASSWORD con al menos 12 caracteres.'
            );
        }

        DB::transaction(function (): void {
            $this->call([
                RolSeeder::class,
                PaisSeeder::class,
                AreaEstudioSeeder::class,
                TipoEstudioSeeder::class,
                IdiomaSeeder::class,
                InstitucionSeeder::class,
                PuestoSeeder::class,
                OrganizacionSeeder::class,
                HabilidadSeeder::class,
                CarreraSeeder::class,
                EstadoPostulacionSeeder::class,
                MotivoRechazoSeeder::class,
                QaSeeder::class,
            ]);
        });

        $this->command?->info(
            'Catalogos y cuentas QA preparados. No se realizo una restauracion completa.'
        );
    }
}
