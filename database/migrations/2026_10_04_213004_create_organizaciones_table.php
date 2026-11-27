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
        Schema::create('organizaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200);
            $table->foreignId('pais_id')->constrained('paises')->restrictOnDelete();
            $table->enum('estado', ['activo', 'inactivo'])->default('inactivo');
            $table->enum('estado_catalogo', ['pendiente', 'aprobado'])->default('pendiente');
            $table->timestamps();
            $table->unique(['nombre', 'pais_id']);
            $table->unique(['id', 'pais_id'], 'organizaciones_id_pais_unique'); // destino de la FK compuesta de experiencias_laborales
        });

        // Solo una organización aprobada puede estar activa (visible para el usuario).
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE organizaciones
                ADD CONSTRAINT chk_organizaciones_activo_solo_aprobado
                CHECK (estado = 'inactivo' OR estado_catalogo = 'aprobado')
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('organizaciones');
    }
};
