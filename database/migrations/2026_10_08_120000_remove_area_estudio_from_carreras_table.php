<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las carreras dejan de depender del área de estudio: solo del tipo de estudio y del país.
     */
    public function up(): void
    {
        Schema::table('candidato_estudios', function (Blueprint $table) {
            $table->dropForeign('fk_estudio_carrera_tipo_area_pais');
        });

        Schema::table('carreras', function (Blueprint $table) {
            $table->dropUnique('carreras_id_tipo_area_pais_unique');
            $table->dropUnique('carreras_nombre_tipo_area_pais_unique');
            $table->dropForeign(['area_estudio_id']);
            $table->dropColumn('area_estudio_id');

            $table->unique(['nombre', 'tipo_estudio_id', 'pais_id'], 'carreras_nombre_tipo_pais_unique');
            $table->unique(['id', 'tipo_estudio_id', 'pais_id'], 'carreras_id_tipo_pais_unique'); // destino de la FK compuesta
        });

        Schema::table('candidato_estudios', function (Blueprint $table) {
            // La carrera debe ser del mismo tipo de estudio y país que el estudio
            $table->foreign(['carrera_id', 'tipo_estudio_id', 'pais_id'], 'fk_estudio_carrera_tipo_pais')
                ->references(['id', 'tipo_estudio_id', 'pais_id'])->on('carreras');
        });

        // MySQL crea un índice con el nombre de la FK vieja y no lo borra junto con ella.
        // Se elimina después de crear la FK nueva, que ya tiene su propio índice desde carrera_id.
        if (Schema::hasIndex('candidato_estudios', 'fk_estudio_carrera_tipo_area_pais')) {
            Schema::table('candidato_estudios', function (Blueprint $table) {
                $table->dropIndex('fk_estudio_carrera_tipo_area_pais');
            });
        }
    }

    /**
     * Reverse the migrations.
     * La columna vuelve como nullable: las áreas de las carreras se pierden al hacer up()
     * y hay que volver a correr el seeder para recuperarlas.
     */
    public function down(): void
    {
        Schema::table('carreras', function (Blueprint $table) {
            $table->foreignId('area_estudio_id')->nullable()->after('tipo_estudio_id')
                ->constrained('areas_estudio')->restrictOnDelete();
            $table->unique(['nombre', 'tipo_estudio_id', 'area_estudio_id', 'pais_id'], 'carreras_nombre_tipo_area_pais_unique');
            $table->unique(['id', 'tipo_estudio_id', 'area_estudio_id', 'pais_id'], 'carreras_id_tipo_area_pais_unique');
        });

        Schema::table('candidato_estudios', function (Blueprint $table) {
            $table->foreign(['carrera_id', 'tipo_estudio_id', 'area_estudio_id', 'pais_id'], 'fk_estudio_carrera_tipo_area_pais')
                ->references(['id', 'tipo_estudio_id', 'area_estudio_id', 'pais_id'])->on('carreras');
            $table->dropForeign('fk_estudio_carrera_tipo_pais');
        });

        if (Schema::hasIndex('candidato_estudios', 'fk_estudio_carrera_tipo_pais')) {
            Schema::table('candidato_estudios', function (Blueprint $table) {
                $table->dropIndex('fk_estudio_carrera_tipo_pais');
            });
        }

        Schema::table('carreras', function (Blueprint $table) {
            $table->dropUnique('carreras_id_tipo_pais_unique');
            $table->dropUnique('carreras_nombre_tipo_pais_unique');
        });
    }
};
