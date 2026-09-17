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

            $table->string('titulo', 100);
            $table->text('descripcion');
            $table->enum('nivel_experiencia', ['junior', 'semi_senior', 'senior']);
            $table->decimal('salario_min', 10, 2);
            $table->decimal('salario_max', 10, 2);
            $table->enum('estado_empleo', ['activo', 'cerrado', 'finalizado'])
                ->default('activo');
            $table->timestamp('fecha_publicacion')->useCurrent();
            $table->dateTime('fecha_cierre')->nullable();
            $table->enum('tipo_empleo', ['presencial', 'remoto', 'hibrido'])->nullable();
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