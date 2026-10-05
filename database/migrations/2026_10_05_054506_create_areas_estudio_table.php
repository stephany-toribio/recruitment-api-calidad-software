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
        Schema::create('areas_estudio', function (Blueprint $table) {
            $table->id();

            $table->string('nombre', 100)
                ->unique();

            $table->boolean('activo')
                ->default(true);

            $table->timestamps();
        });

        DB::statement("
            ALTER TABLE areas_estudio
            ADD CONSTRAINT chk_areas_estudio_activo
            CHECK (activo IN (0, 1))
        ");

        DB::statement("
            ALTER TABLE areas_estudio
            ADD CONSTRAINT chk_areas_estudio_nombre
            CHECK (CHAR_LENGTH(TRIM(nombre)) > 0)
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('areas_estudio');
    }
};
