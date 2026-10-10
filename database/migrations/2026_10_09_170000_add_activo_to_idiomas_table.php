<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El administrador puede desactivar idiomas (HU-01: catálogo normalizado, solo opciones activas).
     * Los idiomas existentes quedan activos. No hay DELETE: candidato_idiomas los referencia (restrict).
     * También se agrega el CHECK de nombre no vacío, como en los demás catálogos.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('idiomas', 'activo')) {
            Schema::table('idiomas', function (Blueprint $table) {
                $table->boolean('activo')->default(true)->after('nombre');
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE idiomas
                ADD CONSTRAINT chk_idiomas_nombre
                CHECK (CHAR_LENGTH(TRIM(nombre)) > 0)
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE idiomas DROP CHECK chk_idiomas_nombre');
        }

        Schema::table('idiomas', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
