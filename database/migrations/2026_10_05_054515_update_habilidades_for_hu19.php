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
        Schema::table('habilidades', function (Blueprint $table) {
            $table->foreignId('area_estudio_id')
                ->nullable()
                ->after('id')
                ->constrained('areas_estudio')
                ->restrictOnDelete();

            $table->boolean('activo')
                ->default(true)
                ->after('tipo_habilidad');
        });

        DB::statement("
            ALTER TABLE habilidades
            MODIFY nombre VARCHAR(100) NOT NULL
        ");

        DB::statement("
            ALTER TABLE habilidades
            MODIFY tipo_habilidad VARCHAR(20) NOT NULL
        ");

        DB::statement("
            ALTER TABLE habilidades
            ADD CONSTRAINT chk_habilidades_activo
            CHECK (activo IN (0, 1))
        ");

        DB::statement("
            ALTER TABLE habilidades
            ADD CONSTRAINT chk_habilidades_nombre
            CHECK (CHAR_LENGTH(TRIM(nombre)) > 0)
        ");

        DB::statement("
            ALTER TABLE habilidades
            ADD CONSTRAINT chk_habilidades_tipo
            CHECK (tipo_habilidad IN ('tecnica', 'blanda'))
        ");

        DB::statement("
            ALTER TABLE habilidades
            ADD CONSTRAINT chk_habilidades_tipo_area
            CHECK (
                (tipo_habilidad = 'tecnica' AND area_estudio_id IS NOT NULL)
                OR
                (tipo_habilidad = 'blanda' AND area_estudio_id IS NULL)
            )
        ");

        Schema::table('habilidades', function (Blueprint $table) {
            $table->dropColumn('estado');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('habilidades', function (Blueprint $table) {
            $table->enum('estado', ['activo', 'inactivo'])
                ->default('activo')
                ->after('tipo_habilidad');
        });

        DB::statement("
            ALTER TABLE habilidades
            DROP CHECK chk_habilidades_tipo_area
        ");

        DB::statement("
            ALTER TABLE habilidades
            DROP CHECK chk_habilidades_tipo
        ");

        DB::statement("
            ALTER TABLE habilidades
            DROP CHECK chk_habilidades_nombre
        ");

        DB::statement("
            ALTER TABLE habilidades
            DROP CHECK chk_habilidades_activo
        ");

        DB::statement("
            ALTER TABLE habilidades
            MODIFY tipo_habilidad VARCHAR(255) NULL
        ");

        DB::statement("
            ALTER TABLE habilidades
            MODIFY nombre VARCHAR(255) NOT NULL
        ");

        Schema::table('habilidades', function (Blueprint $table) {
            $table->dropForeign(['area_estudio_id']);

            $table->dropColumn('area_estudio_id');
            $table->dropColumn('activo');
        });
    }
};
