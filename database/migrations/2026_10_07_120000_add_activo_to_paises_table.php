<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Permite al administrador habilitar o deshabilitar países.
     * Los países ya existentes quedan activos.
     */
    public function up(): void
    {
        Schema::table('paises', function (Blueprint $table) {
            $table->boolean('activo')->default(true)->after('nombre');
        });
    }

    public function down(): void
    {
        Schema::table('paises', function (Blueprint $table) {
            $table->dropColumn('activo');
        });
    }
};
