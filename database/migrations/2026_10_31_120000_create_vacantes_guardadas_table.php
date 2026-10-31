<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vacantes_guardadas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('candidato_id')
                ->constrained('candidatos')
                ->onDelete('cascade');
            $table->foreignId('empleo_id')
                ->constrained('empleos')
                ->onDelete('restrict');

            $table->timestamps();
            $table->unique(['candidato_id', 'empleo_id']);
            $table->index(['candidato_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vacantes_guardadas');
    }
};
