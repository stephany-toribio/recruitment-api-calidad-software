<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Agrega la restricción de integridad para impedir
     * nombres vacíos o compuestos únicamente por espacios.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE instituciones
            ADD CONSTRAINT chk_instituciones_nombre
            CHECK (CHAR_LENGTH(TRIM(nombre)) > 0)
        ");
    }

    /**
     * Revierte la restricción agregada.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE instituciones
            DROP CHECK chk_instituciones_nombre
        ");
    }
};
