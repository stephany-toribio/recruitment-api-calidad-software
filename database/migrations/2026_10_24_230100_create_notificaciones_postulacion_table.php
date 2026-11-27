<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notificaciones_postulacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('postulacion_id')->constrained('postulaciones')->cascadeOnDelete();
            $table->enum('tipo', ['contratado', 'rechazado']);
            $table->string('destinatario');
            $table->enum('resultado', ['enviado', 'fallido']);
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index('resultado');
        });

        if (DB::getDriverName() === 'mysql') {
            // Un envío fallido guarda el error del proveedor; uno exitoso no
            DB::statement("
                ALTER TABLE notificaciones_postulacion
                ADD CONSTRAINT chk_notificaciones_postulacion_error
                CHECK (
                    (resultado = 'enviado' AND error IS NULL)
                    OR (resultado = 'fallido' AND error IS NOT NULL)
                )
            ");
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones_postulacion');
    }
};
