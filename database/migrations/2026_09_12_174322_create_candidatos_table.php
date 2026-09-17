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
                ->unique()
                ->constrained('usuarios')
                ->onDelete('cascade');

            $table->string('nombre');
            $table->string('apellido_paterno');
            $table->string('apellido_materno'); 
            $table->string('dni', 8)->unique()->nullable();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('celular', 20);
            $table->enum('genero', ['masculino', 'femenino', 'otro'])->nullable();
            $table->enum('disponibilidad', ['inmediata', '1_semana', '1_mes'])->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('candidatos');
    }
};