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
            $table->string('apellido_paterno');
            $table->string('apellido_materno'); 
            $table->string('dni', 8)->unique(); 
            $table->date('fecha_nacimiento');
            $table->string('celular', 20);
            $table->enum('genero', ['masculino', 'femenino', 'otro'])->default('masculino');
            $table->enum('disponibilidad', ['inmediata', '1_semana', '1_mes'])->default('inmediata');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidatos');
    }
};