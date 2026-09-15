<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usuarios', function (Blueprint $table) {
            $table->id();
            $table->string('correo')->unique();
            $table->string('password');
            $table->foreignId('rol_id')
                ->constrained('roles')
                ->onDelete('restrict');

            $table->enum('estado_cuenta', ['activo', 'suspendido'])->default('activo');
            $table->unsignedTinyInteger('intentos_fallidos')->default(0);
            $table->dateTime('bloqueado_hasta')->nullable();
            $table->string('motivo_suspension')->nullable();
            $table->timestamp('fecha_suspension')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usuarios');
    }
}; 