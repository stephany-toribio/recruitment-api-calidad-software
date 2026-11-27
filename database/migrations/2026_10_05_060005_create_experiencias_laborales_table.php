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
        Schema::create('experiencias_laborales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('candidato_id')->constrained('candidatos')->cascadeOnDelete();
            $table->foreignId('puesto_id')->constrained('puestos')->restrictOnDelete();
            $table->foreignId('organizacion_id')->constrained('organizaciones')->restrictOnDelete();
            $table->foreignId('pais_id')->constrained('paises')->restrictOnDelete();      
            $table->enum('nivel', ['practicante_pre', 'practicante_pro', 'trainee', 'junior', 'semi_senior', 'senior', 'jefe_supervisor', 'gerente_director']);
            $table->date('fecha_inicio');
            $table->date('fecha_fin')->nullable();
            $table->text('descripcion')->nullable(); 
            $table->timestamps();

            // El país de la experiencia debe ser el país de la organización.
            $table->foreign(['organizacion_id', 'pais_id'], 'fk_experiencia_organizacion_pais')
                ->references(['id', 'pais_id'])->on('organizaciones');
        });

        // La fecha de fin (si existe) no puede ser anterior a la de inicio. 
        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE experiencias_laborales
                ADD CONSTRAINT chk_experiencias_laborales_fechas
                CHECK (fecha_fin IS NULL OR fecha_fin >= fecha_inicio)
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('experiencias_laborales');
    }
};
