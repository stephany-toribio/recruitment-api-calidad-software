<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleo_habilidades', function (Blueprint $table) {
            $table->foreignId('empleo_id')
                ->constrained('empleos')
                ->onDelete('cascade');

            $table->foreignId('habilidad_id')
                ->constrained('habilidades')
                ->onDelete('restrict'); 
                
            $table->unsignedTinyInteger('peso');
            $table->timestamps();

            $table->primary(['empleo_id', 'habilidad_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleo_habilidades');
    }
};