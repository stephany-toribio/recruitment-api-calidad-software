<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('puestos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200)->unique();
            $table->enum('estado', ['activo', 'inactivo'])->default('inactivo');
            $table->enum('estado_catalogo', ['pendiente', 'aprobado'])->default('pendiente');
            $table->timestamps();
        });

        // Solo un puesto aprobado puede estar activo (visible para el usuario).

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE puestos
                ADD CONSTRAINT chk_puestos_activo_solo_aprobado
                CHECK (estado = 'inactivo' OR estado_catalogo = 'aprobado')
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('puestos');
    }
};
