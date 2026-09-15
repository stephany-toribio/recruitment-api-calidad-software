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
        Schema::create('candidato_habilidades', function (Blueprint $table) {
            

            $table->foreignId('candidato_id')
                ->constrained('candidatos')
                ->onDelete('cascade');

            $table->foreignId('habilidad_id')
                ->constrained('habilidades')
                ->onDelete('cascade');
            
            $table->primary(['candidato_id', 'habilidad_id']);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidato_habilidades');
    }
};
