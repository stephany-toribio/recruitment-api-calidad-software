<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * HU-01: nivel educativo del candidato.
     * Nullable en BD para no romper candidatos ya registrados; la obligatoriedad
     * para nuevos registros la controla RegistrarCandidatoRequest.
     */
    public function up(): void
    {
        Schema::table('candidatos', function (Blueprint $table) {
            $table->enum('nivel_educativo', ['secundaria', 'tecnico', 'universitario', 'posgrado'])
                ->nullable()
                ->after('genero');
        });
    }

    public function down(): void
    {
        Schema::table('candidatos', function (Blueprint $table) {
            $table->dropColumn('nivel_educativo');
        });
    }
};
