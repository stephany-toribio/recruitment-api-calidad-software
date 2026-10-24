<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Decisión del administrador sobre una vacante (HU-22).
     * Una vacante sin fila en esta tabla está pendiente de revisión.
     */
    public function up(): void
    {
        Schema::create('revisiones_vacante', function (Blueprint $table) {
            $table->id();
            // unique: la decisión es definitiva, solo hay una por vacante (también frena dos aprobaciones simultáneas)
            $table->foreignId('empleo_id')->unique()->constrained('empleos')->restrictOnDelete();
            $table->foreignId('admin_id')->constrained('usuarios')->restrictOnDelete();
            $table->enum('decision', ['aprobada', 'no_aprobada']);
            $table->text('motivo')->nullable();
            $table->timestamps();
        });

        if (DB::getDriverName() === 'mysql') {
            // No aprobar exige un motivo que no sea solo espacios
            DB::statement("
                ALTER TABLE revisiones_vacante
                ADD CONSTRAINT chk_revisiones_vacante_motivo
                CHECK (
                    decision = 'aprobada'
                    OR (motivo IS NOT NULL AND CHAR_LENGTH(TRIM(motivo)) > 0)
                )
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('revisiones_vacante');
    }
};
