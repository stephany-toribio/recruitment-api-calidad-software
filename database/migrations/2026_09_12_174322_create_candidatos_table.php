<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('candidatos', function (Blueprint $table) {
            
            $table->id();
 
            $table->foreignId('usuario_id')
                ->constrained('usuarios')
                ->onDelete('cascade');

            $table->string('nombre');
            $table->string('apellido')->nullable();
            $table->string('dni', 8)->unique(); 
            $table->date('fecha_nacimiento');
            $table->string('telefono', 20);
            $table->decimal('expectativa_salarial', 10, 2);
            $table->enum('disponibilidad', ['inmediata', '1_semana', '1_mes'])->default('inmediata');
            $table->string('cv_path')->nullable(); 
            $table->string('foto_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidatos');
    }
};