<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('candidatos', function (Blueprint $table) {
            $table->boolean('busca_primer_empleo')->default(false)->after('genero');
            $table->dropColumn('disponibilidad');
            $table->dropColumn('nivel_educativo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidatos', function (Blueprint $table) {
            $table->dropColumn('busca_primer_empleo');
            $table->enum('nivel_educativo', ['secundaria', 'tecnico', 'universitario', 'posgrado'])->nullable()->after('genero');
        });

        // En una segunda instrucción, para que nivel_educativo ya exista al usar after().
        Schema::table('candidatos', function (Blueprint $table) {
            $table->enum('disponibilidad', ['inmediata', '1_semana', '1_mes'])->nullable()->after('nivel_educativo');
        });
    }
};
