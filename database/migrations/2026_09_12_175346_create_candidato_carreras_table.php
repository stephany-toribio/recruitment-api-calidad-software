<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
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

            // Evita duplicar la misma carrera e institucion para el mismo candidato
            $table->unique(['candidato_id', 'carrera_id', 'institucion'], 'candidato_carrera_inst_unique');
        });

    }
    public function down(): void
    {
        Schema::dropIfExists('candidato_carreras');
    }
};
