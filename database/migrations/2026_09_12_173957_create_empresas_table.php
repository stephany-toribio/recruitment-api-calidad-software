<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id(); 

            $table->foreignId('usuario_id')
                ->unique()
                ->constrained('usuarios')
                ->onDelete('cascade');

            $table->string('razon_social');
            $table->text('descripcion')->nullable();
            $table->char('ruc', 11)->unique();
            $table->string('sitio_web');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};