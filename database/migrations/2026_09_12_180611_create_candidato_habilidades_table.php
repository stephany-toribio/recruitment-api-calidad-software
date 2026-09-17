<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidato_habilidades', function (Blueprint $table) {
            
            $table->foreignId('candidato_id')
                ->constrained('candidatos')
                ->onDelete('cascade');
            $table->foreignId('habilidad_id')
                ->constrained('habilidades')
                ->onDelete('restrict');
            
            $table->primary(['candidato_id', 'habilidad_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidato_habilidades');
    }
};
