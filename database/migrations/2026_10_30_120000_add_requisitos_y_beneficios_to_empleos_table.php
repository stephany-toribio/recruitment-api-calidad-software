<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Requisitos y beneficios de la vacante (HU-03): listas de textos libres que la empresa
 * escribe en el formulario. Se guardan como JSON porque solo se muestran: no se filtra
 * ni se busca por ellos, así que no justifican tablas propias.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleos', function (Blueprint $table) {
            $table->json('requisitos')->nullable()->after('descripcion');
            $table->json('beneficios')->nullable()->after('requisitos');
        });
    }

    public function down(): void
    {
        Schema::table('empleos', function (Blueprint $table) {
            $table->dropColumn(['requisitos', 'beneficios']);
        });
    }
};
