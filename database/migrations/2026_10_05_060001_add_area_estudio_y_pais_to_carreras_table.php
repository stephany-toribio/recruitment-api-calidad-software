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
        Schema::table('carreras', function (Blueprint $table) {
            // Las carreras dependen del tipo de estudio (Universitario, Master...), del área y del país
            $table->foreignId('tipo_estudio_id')->after('id')->constrained('tipos_estudio')->restrictOnDelete();
            $table->foreignId('area_estudio_id')->after('tipo_estudio_id')->constrained('areas_estudio')->restrictOnDelete();
            $table->foreignId('pais_id')->after('area_estudio_id')->constrained('paises')->restrictOnDelete();
            $table->dropUnique(['nombre']);
            $table->unique(['nombre', 'tipo_estudio_id', 'area_estudio_id', 'pais_id'], 'carreras_nombre_tipo_area_pais_unique');
            $table->unique(['id', 'tipo_estudio_id', 'area_estudio_id', 'pais_id'], 'carreras_id_tipo_area_pais_unique'); // destino de la FK compuesta de candidato_estudios

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carreras', function (Blueprint $table) {
            $table->dropUnique('carreras_id_tipo_area_pais_unique');
            $table->dropUnique('carreras_nombre_tipo_area_pais_unique');
            $table->dropForeign(['tipo_estudio_id']);
            $table->dropForeign(['area_estudio_id']);
            $table->dropForeign(['pais_id']);
            $table->dropColumn(['tipo_estudio_id', 'area_estudio_id', 'pais_id']);
            $table->unique('nombre');
        });
    }
};
