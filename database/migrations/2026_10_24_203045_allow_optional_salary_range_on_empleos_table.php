<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE empleos DROP CHECK chk_empleos_salarios'
            );
        }

        Schema::table('empleos', function (Blueprint $table) {
            $table->decimal('salario_min', 10, 2)
                ->nullable()
                ->change();

            $table->decimal('salario_max', 10, 2)
                ->nullable()
                ->change();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                ALTER TABLE empleos
                ADD CONSTRAINT chk_empleos_salarios
                CHECK (
                    (
                        salario_min IS NULL
                        AND salario_max IS NULL
                    )
                    OR
                    (
                        salario_min IS NOT NULL
                        AND salario_max IS NOT NULL
                        AND salario_min > 0
                        AND salario_max >= salario_min
                    )
                )
            ');
        }
    }

    public function down(): void
    {
        if (
            DB::table('empleos')
                ->where(function ($query) {
                    $query->whereNull('salario_min')
                        ->orWhereNull('salario_max');
                })
                ->exists()
        ) {
            throw new RuntimeException(
                'No se puede revertir: existen vacantes sin rango salarial completo.'
            );
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'ALTER TABLE empleos DROP CHECK chk_empleos_salarios'
            );
        }

        Schema::table('empleos', function (Blueprint $table) {
            $table->decimal('salario_min', 10, 2)
                ->nullable(false)
                ->change();

            $table->decimal('salario_max', 10, 2)
                ->nullable(false)
                ->change();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('
                ALTER TABLE empleos
                ADD CONSTRAINT chk_empleos_salarios
                CHECK (
                    salario_min > 0
                    AND salario_max >= salario_min
                )
            ');
        }
    }
};
