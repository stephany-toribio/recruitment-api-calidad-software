<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('postulaciones', function (Blueprint $table) {
            $table->id();
  
            $table->foreignId('candidato_id')
                ->constrained('candidatos')
                ->onDelete('cascade');
            $table->foreignId('empleo_id')
                ->constrained('empleos')
                ->onDelete('restrict');
            $table->foreignId('estado_postulacion_id')
                ->constrained('estados_postulacion')
                ->onDelete('restrict');

            $table->unsignedTinyInteger('porcentaje_match')->nullable();
            $table->timestamp('fecha_postulacion')->useCurrent();
            $table->timestamps();                       
            $table->unique(['candidato_id', 'empleo_id']);
            $table->index(['empleo_id', 'estado_postulacion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postulaciones');
    }
};