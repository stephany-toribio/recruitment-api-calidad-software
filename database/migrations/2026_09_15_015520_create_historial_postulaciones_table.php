<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        
        Schema::create('historial_postulaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('postulacion_id')
                ->constrained('postulaciones')
                ->onDelete('cascade');

            
            $table->enum('estado_anterior', [
                'recibido',
                'en_revision',
                'entrevista',
                'contratado',
                'rechazado',
            ])->nullable();

            
            $table->enum('estado_nuevo', [
                'recibido',
                'en_revision',
                'entrevista',
                'contratado',
                'rechazado',
            ]);

            

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_postulaciones');
    }
};