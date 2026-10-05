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
        Schema::dropIfExists('candidato_carreras');
    }

    /**
     * Reverse the migrations.
     * Recrea la tabla idéntica a 2026_09_12_175346_create_candidato_carreras_table.
     */
    public function down(): void
    {
        Schema::create('candidato_carreras', function (Blueprint $table) {
            $table->id();

            $table->foreignId('candidato_id')
                ->constrained('candidatos')
                ->onDelete('cascade');
            $table->foreignId('carrera_id')
                ->constrained('carreras')
                ->onDelete('cascade');

            $table->string('area')->nullable();
            $table->string('institucion');
            $table->enum('estado', ['en_curso', 'egresado', 'retirado']);
            $table->unsignedTinyInteger('ciclo_actual')->nullable();
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->timestamps();

            $table->unique(['candidato_id', 'carrera_id', 'institucion'], 'candidato_carrera_inst_unique');
        });
    }
};
