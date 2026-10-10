<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDICE = 'candidatos_celular_unique';

    /**
     * El celular del candidato pasa a ser único (como el DNI). Las empresas no tienen teléfono,
     * así que la unicidad es solo entre candidatos.
     *
     * Si ya hay números repetidos la migración se detiene: no se pueden inventar teléfonos para
     * "arreglarlos". En una base local de pruebas lo más simple es: php artisan migrate:fresh --seed
     */
    public function up(): void
    {
        if (Schema::hasIndex('candidatos', self::INDICE)) {
            return;
        }

        $repetidos = DB::table('candidatos')
            ->select('celular', DB::raw('COUNT(*) as total'))
            ->groupBy('celular')
            ->having('total', '>', 1)
            ->pluck('total', 'celular');

        if ($repetidos->isNotEmpty()) {
            $detalle = $repetidos->map(fn ($total, $celular) => "{$celular} ({$total} candidatos)")->implode(', ');

            throw new RuntimeException(
                "No se puede hacer único el celular: hay números repetidos: {$detalle}. "
                . 'Corrígelos antes de migrar o, en una base local de pruebas, ejecuta php artisan migrate:fresh --seed.'
            );
        }

        Schema::table('candidatos', function (Blueprint $table) {
            $table->unique('celular', self::INDICE);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidatos', function (Blueprint $table) {
            $table->dropUnique(self::INDICE);
        });
    }
};
