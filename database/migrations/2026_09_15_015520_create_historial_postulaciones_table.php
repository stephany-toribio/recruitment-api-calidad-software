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

            
            $table->foreignId('estado_postulacion_id')
                ->constrained('estados_postulacion')
                ->onDelete('restrict');

            $table->foreignId('motivo_rechazo_id')
                ->nullable()
                ->constrained('motivos_rechazo')
                ->nullOnDelete();

            $table->timestamp('fecha_cambio')->useCurrent();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historial_postulaciones');
    }
};