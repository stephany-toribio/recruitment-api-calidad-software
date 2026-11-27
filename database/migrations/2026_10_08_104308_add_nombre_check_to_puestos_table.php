<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE puestos
                ADD CONSTRAINT chk_puestos_nombre
                CHECK (CHAR_LENGTH(TRIM(nombre)) > 0)
            ");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE puestos
                DROP CHECK chk_puestos_nombre
            ");
        }
    }
};
