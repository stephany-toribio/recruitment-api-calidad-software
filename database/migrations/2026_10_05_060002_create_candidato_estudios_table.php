<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('candidato_estudios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidato_id')->constrained('candidatos')->cascadeOnDelete();
            $table->foreignId('tipo_estudio_id')->constrained('tipos_estudio')->restrictOnDelete();
            $table->foreignId('area_estudio_id')->constrained('areas_estudio')->restrictOnDelete(); 
            $table->foreignId('pais_id')->constrained('paises')->restrictOnDelete();
            // NULL en Secundario y Terciario: el catálogo de instituciones solo tiene universidades
            $table->foreignId('institucion_id')->nullable()->constrained('instituciones')->restrictOnDelete();
            // NULL en Secundario y Terciario. En los demás tipos se elige del catálogo (sin texto libre)
            $table->foreignId('carrera_id')->nullable()->constrained('carreras');
            $table->enum('estado', ['finalizado', 'en_curso', 'incompleto']);
            $table->unsignedTinyInteger('ciclo_actual')->nullable();
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->timestamps();

            // La carrera debe ser del mismo tipo de estudio, área y país que el estudio
            $table->foreign(['carrera_id', 'tipo_estudio_id', 'area_estudio_id', 'pais_id'], 'fk_estudio_carrera_tipo_area_pais')
                ->references(['id', 'tipo_estudio_id', 'area_estudio_id', 'pais_id'])->on('carreras');
            $table->foreign(['institucion_id', 'pais_id'], 'fk_estudio_institucion_pais')
                ->references(['id', 'pais_id'])->on('instituciones');

            $table->unique(['candidato_id', 'tipo_estudio_id', 'institucion_id', 'carrera_id'], 'candidato_estudio_unique');

        });

        if (DB::getDriverName() === 'mysql') {
            // La fecha de fin (si existe) no puede ser anterior a la de inicio.
            DB::statement("
                ALTER TABLE candidato_estudios
                ADD CONSTRAINT chk_candidato_estudios_fechas
                CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio)
            ");

            // El ciclo actual solo tiene sentido en un estudio en curso.
            DB::statement("
                ALTER TABLE candidato_estudios
                ADD CONSTRAINT chk_candidato_estudios_ciclo_en_curso
                CHECK (ciclo_actual IS NULL OR estado = 'en_curso')
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidato_estudios');
    }
};
