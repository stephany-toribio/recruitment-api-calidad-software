<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('postulaciones', function (Blueprint $table) {
            // NULL mientras la postulación sigue activa; la etapa de selección no cambia al retirarse
            $table->timestamp('retirada_en')->nullable()->after('fecha_postulacion');
        });
    }

    public function down(): void
    {
        Schema::table('postulaciones', function (Blueprint $table) {
            $table->dropColumn('retirada_en');
        });
    }
};
