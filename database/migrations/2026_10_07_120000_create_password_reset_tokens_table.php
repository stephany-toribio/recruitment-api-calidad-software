<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     *
     * Un proceso de recuperación de contraseña por usuario 
     * 1. Se envía un código de 6 dígitos al correo (solo se guarda su hash).
     * 2. Si el código es correcto, se emite un reset_token de vida corta (solo se guarda su hash).
     * 3. Al restablecer la contraseña, el registro se elimina (un solo uso).
     */
    public function up(): void
    {
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->id();
            // unique: una nueva solicitud reemplaza a la anterior y el código viejo deja de servir
            $table->foreignId('usuario_id')->unique()->constrained('usuarios')->cascadeOnDelete();
            $table->string('codigo_hash');
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->dateTime('codigo_expira_en');
            $table->string('reset_token_hash')->nullable();
            $table->dateTime('reset_token_expira_en')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('password_reset_tokens');
    }
};
