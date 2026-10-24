<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const REGLA = "
        tipo_empleo IS NOT NULL
        AND tipo_empleo IN ('presencial', 'remoto', 'hibrido')
        AND (
            (
                tipo_empleo = 'remoto'
                AND (
                    ubicacion IS NULL
                    OR REGEXP_LIKE(ubicacion, '[^[:space:]]')
                )
            )
            OR
            (
                tipo_empleo IN ('presencial', 'hibrido')
                AND ubicacion IS NOT NULL
                AND REGEXP_LIKE(ubicacion, '[^[:space:]]')
            )
        )
    ";

    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // No inventar modalidad o ubicación para registros anteriores.
        $hayInvalidas = DB::table('empleos')
            ->whereRaw('NOT ('.self::REGLA.')')
            ->exists();

        if ($hayInvalidas) {
            throw new RuntimeException(
                'Existen vacantes con modalidad o ubicación inválida. Corrijan esos datos antes de aplicar la restricción.'
            );
        }

        DB::statement(
            'ALTER TABLE empleos
             ADD CONSTRAINT chk_empleos_modalidad_ubicacion
             CHECK ('.self::REGLA.')'
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE empleos
                 DROP CHECK chk_empleos_modalidad_ubicacion'
            );
        }
    }
};
