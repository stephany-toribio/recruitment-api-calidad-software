<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Código del enum anterior → nombre del nivel global (los inserta 2026_10_09_140000_create_niveles_puesto_table)
    private const CODIGOS = [
        'practicante_pre'  => 'Practicante preprofesional',
        'practicante_pro'  => 'Practicante profesional',
        'trainee'          => 'Trainee',
        'junior'           => 'Junior',
        'semi_senior'      => 'Semi Senior',
        'senior'           => 'Senior',
        'jefe_supervisor'  => 'Jefe / Supervisor',
        'gerente_director' => 'Gerente / Director',
    ];

    private const FK = 'experiencias_laborales_nivel_puesto_id_foreign';

    
    public function up(): void
    {
        if (! Schema::hasColumn('experiencias_laborales', 'nivel_puesto_id')) {
            Schema::table('experiencias_laborales', function (Blueprint $table) {
                $table->unsignedBigInteger('nivel_puesto_id')->nullable()->after('organizacion_id');
            });
        }

        if (Schema::hasColumn('experiencias_laborales', 'nivel')) {
            $niveles = DB::table('niveles_puesto')->whereNull('candidato_id')->pluck('id', 'nombre');

            foreach (self::CODIGOS as $codigo => $nombre) {
                DB::table('experiencias_laborales')
                    ->where('nivel', $codigo)
                    ->update(['nivel_puesto_id' => $niveles[$nombre]]);
            }
        }

        // Si quedó de un intento anterior, la FK (y su índice) se sueltan para poder cambiar la columna
        if ($this->tieneFk()) {
            Schema::table('experiencias_laborales', function (Blueprint $table) {
                $table->dropForeign(self::FK);
            });
        }

        if (Schema::hasIndex('experiencias_laborales', self::FK)) {
            Schema::table('experiencias_laborales', function (Blueprint $table) {
                $table->dropIndex(self::FK);
            });
        }

        Schema::table('experiencias_laborales', function (Blueprint $table) {
            $table->unsignedBigInteger('nivel_puesto_id')->nullable(false)->change();
        });

        Schema::table('experiencias_laborales', function (Blueprint $table) {
            $table->foreign('nivel_puesto_id', self::FK)
                ->references('id')->on('niveles_puesto')->restrictOnDelete();
        });

        if (Schema::hasColumn('experiencias_laborales', 'nivel')) {
            Schema::table('experiencias_laborales', function (Blueprint $table) {
                $table->dropColumn('nivel');
            });
        }
    }

    private function tieneFk(): bool
    {
        return collect(Schema::getForeignKeys('experiencias_laborales'))->contains('name', self::FK);
    }

    
    public function down(): void
    {
        Schema::table('experiencias_laborales', function (Blueprint $table) {
            $table->enum('nivel', array_keys(self::CODIGOS))->nullable()->after('pais_id');
        });

        $niveles = DB::table('niveles_puesto')->whereNull('candidato_id')->pluck('id', 'nombre');

        foreach (self::CODIGOS as $codigo => $nombre) {
            if (isset($niveles[$nombre])) {
                DB::table('experiencias_laborales')
                    ->where('nivel_puesto_id', $niveles[$nombre])
                    ->update(['nivel' => $codigo]);
            }
        }

        Schema::table('experiencias_laborales', function (Blueprint $table) {
            $table->dropForeign(self::FK);
            $table->dropColumn('nivel_puesto_id');
        });
    }
};
