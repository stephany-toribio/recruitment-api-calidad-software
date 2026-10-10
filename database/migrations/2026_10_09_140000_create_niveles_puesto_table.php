<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public const CODIGOS = [
        'practicante_pre'  => 'Practicante preprofesional',
        'practicante_pro'  => 'Practicante profesional',
        'trainee'          => 'Trainee',
        'junior'           => 'Junior',
        'semi_senior'      => 'Semi Senior',
        'senior'           => 'Senior',
        'jefe_supervisor'  => 'Jefe / Supervisor',
        'gerente_director' => 'Gerente / Director',
    ];

    public function up(): void
    {
        Schema::create('niveles_puesto', function (Blueprint $table) {
            $table->id();
            // restrict: los candidatos no se eliminan (se suspende al usuario) y MySQL no permite
            // CASCADE / SET NULL en la base de una columna generada (dueno_id)
            $table->foreignId('candidato_id')->nullable()->constrained('candidatos')->restrictOnDelete();
            $table->unsignedBigInteger('dueno_id')->storedAs('COALESCE(candidato_id, 0)');
            $table->string('nombre', 100);
            $table->unsignedSmallInteger('orden')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();

            $table->unique(['nombre', 'dueno_id'], 'niveles_puesto_nombre_dueno_unique');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE niveles_puesto
                ADD CONSTRAINT chk_niveles_puesto_nombre
                CHECK (CHAR_LENGTH(TRIM(nombre)) > 0)
            ");
        }

        $orden = 0;
        foreach (self::CODIGOS as $nombre) {
            DB::table('niveles_puesto')->insert([
                'nombre'     => $nombre,
                'orden'      => ++$orden,
                'estado'     => 'activo',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('niveles_puesto');
    }
};
