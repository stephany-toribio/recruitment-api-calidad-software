<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empleos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('empresa_id')
                ->constrained('empresas')
                ->onDelete('cascade');

            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->string('nivel_experiencia')->nullable();
            $table->decimal('salario_min', 10, 2)->nullable();
            $table->decimal('salario_max', 10, 2)->nullable();
            $table->enum('estado_empleo', ['activo', 'cerrado', 'finalizado'])
                ->default('activo');
            $table->dateTime('fecha_publicacion')->nullable();
            $table->dateTime('fecha_cierre')->nullable();
            $table->enum('tipo_empleo', ['presencial', 'remoto', 'hibrido'])
                ->default('presencial');
            $table->unsignedSmallInteger('numero_vacantes')->default(1);
            

            $table->timestamps();

            $table->index(['estado_empleo', 'tipo_empleo']);

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empleos');
    }
};