<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Una vacante puede revisarse más de una vez: si la empresa la edita después de aprobada,
     * esa aprobación se invalida y la vacante vuelve a pendiente.
     * - Se quita el UNIQUE de empleo_id: las decisiones anteriores quedan como historial.
     * - invalidada_en marca las revisiones que ya no valen. La revisión vigente es la que lo tiene en NULL.
     */
    public function up(): void
    {
        Schema::table('revisiones_vacante', function (Blueprint $table) {
            $table->timestamp('invalidada_en')->nullable()->after('motivo');

            // Va antes de quitar el UNIQUE: MySQL necesita un índice en empleo_id para su FK
            $table->index(['empleo_id', 'invalidada_en']);

            $table->dropUnique(['empleo_id']);
        });
    }

    /**
     * Reverse the migrations.
     * Falla si alguna vacante ya tiene más de una revisión: habría que decidir antes cuál conservar.
     */
    public function down(): void
    {
        Schema::table('revisiones_vacante', function (Blueprint $table) {
            $table->unique('empleo_id');
            $table->dropIndex(['empleo_id', 'invalidada_en']);
            $table->dropColumn('invalidada_en');
        });
    }
};
