<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * El administrador puede desactivar carreras (HU-01: "solo deben mostrarse opciones activas").
     * Las carreras existentes quedan activas. No hay DELETE: los estudios de los candidatos las referencian.
     * También se agrega el CHECK de nombre no vacío, como en los demás catálogos.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('carreras', 'activo')) {
            Schema::table('carreras', function (Blueprint $table) {
                $table->boolean('activo')->default(true)->after('nombre');
            });
        }

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE carreras
                ADD CONSTRAINT chk_carreras_nombre
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
            DB::statement('ALTER TABLE carreras DROP CHECK chk_carreras_nombre');
        }

        Schema::table('carreras', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
