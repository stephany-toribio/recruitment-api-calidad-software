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

            $table->decimal('porcentaje_match', 5, 2)->nullable();
            

            $table->enum('estado', [
                'recibido',
                'en_revision',
                'entrevista',
                'contratado',
                'rechazado',
            ])->default('recibido');

            $table->timestamps();                       

            $table->unique(['candidato_id', 'empleo_id']);

            $table->index(['empleo_id', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('postulaciones');
    }
};