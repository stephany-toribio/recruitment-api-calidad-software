<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        if (
            DB::table('empleos')
                ->whereNull('area_estudio_id')
                ->exists()
        ) {
            throw new RuntimeException(
                'Existen vacantes sin área. Asignen sus áreas antes de aplicar esta restricción.'
            );
        }

        DB::statement('
            ALTER TABLE empleos
            ADD CONSTRAINT chk_empleos_area_obligatoria
            CHECK (area_estudio_id IS NOT NULL)
        ');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE empleos
                 DROP CHECK chk_empleos_area_obligatoria'
            );
        }
    }
};
