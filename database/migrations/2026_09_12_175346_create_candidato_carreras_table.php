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
        Schema::create('candidato_carreras', function (Blueprint $table) {
            $table->id();

            $table->foreignId('candidato_id')
                ->constrained('candidatos')
                ->onDelete('cascade');

            $table->string('nombre_carrera');
            $table->string('nombre_institucion');
                
            $table->enum('estado', ['en_curso', 'egresado', 'titulado'])->default('egresado');
            $table->unsignedTinyInteger('ciclo_actual')->nullable();
    
            $table->timestamps();

            //Evita duplicar la misma carrera para el mismo candidato
            $table->unique(['candidato_id', 'nombre_carrera', 'nombre_institucion'], 'candidato_carrera_inst_unique');
        });

    }
    public function down(): void
    {
        Schema::dropIfExists('candidato_carreras');
    }
};
