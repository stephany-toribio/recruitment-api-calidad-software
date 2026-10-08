<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Agrega restricciones de integridad al catálogo de países.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE paises
            ADD CONSTRAINT chk_paises_activo
            CHECK (activo IN (0, 1))
        ");

        DB::statement("
            ALTER TABLE paises
            ADD CONSTRAINT chk_paises_nombre
            CHECK (CHAR_LENGTH(TRIM(nombre)) > 0)
        ");
    }

    /**
     * Revierte las restricciones agregadas.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE paises
            DROP CHECK chk_paises_nombre
        ");

        DB::statement("
            ALTER TABLE paises
            DROP CHECK chk_paises_activo
        ");
    }
};
