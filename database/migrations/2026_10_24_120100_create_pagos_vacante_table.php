<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Intentos de pago simulado de una vacante aprobada (HU-03).
     * Una vacante está pagada cuando tiene un intento con resultado 'exitoso'.
     */
    public function up(): void
    {
        Schema::create('pagos_vacante', function (Blueprint $table) {
            $table->id();
            // sin unique: una vacante puede tener intentos fallidos o cancelados antes del exitoso
            $table->foreignId('empleo_id')->constrained('empleos')->restrictOnDelete();
            $table->enum('resultado', ['exitoso', 'fallido', 'cancelado']);
            $table->decimal('monto', 10, 2);
            $table->char('moneda', 3)->default('PEN');
            // solo los últimos 4 dígitos de la tarjeta de prueba; nunca el número completo ni el CVV
            $table->char('tarjeta_ultimos4', 4)->nullable();
            $table->timestamps();

            $table->index(['empleo_id', 'resultado']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE pagos_vacante
                ADD CONSTRAINT chk_pagos_vacante_monto
                CHECK (monto > 0)
            ");

            // Un pago cancelado no llega a usar tarjeta; uno exitoso o fallido sí
            DB::statement("
                ALTER TABLE pagos_vacante
                ADD CONSTRAINT chk_pagos_vacante_tarjeta
                CHECK (
                    (resultado = 'cancelado' AND tarjeta_ultimos4 IS NULL)
                    OR (resultado <> 'cancelado' AND tarjeta_ultimos4 IS NOT NULL)
                )
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_vacante');
    }
};
