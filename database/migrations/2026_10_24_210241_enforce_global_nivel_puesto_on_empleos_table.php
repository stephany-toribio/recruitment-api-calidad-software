<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $hayPersonalizados = DB::table('empleos')
            ->join(
                'niveles_puesto',
                'niveles_puesto.id',
                '=',
                'empleos.nivel_puesto_id'
            )
            ->whereNotNull('niveles_puesto.candidato_id')
            ->exists();

        if ($hayPersonalizados) {
            throw new RuntimeException(
                'Existen vacantes con niveles personalizados. Asignen niveles globales antes de continuar.'
            );
        }

        Schema::table('niveles_puesto', function (Blueprint $table) {
            $table->unique(
                ['id', 'dueno_id'],
                'niveles_puesto_id_dueno_unique'
            );
        });

        Schema::table('empleos', function (Blueprint $table) {
            $table->unsignedBigInteger('nivel_puesto_dueno_id')
                ->storedAs('0');
        });

        Schema::table('empleos', function (Blueprint $table) {
            $table->foreign(
                ['nivel_puesto_id', 'nivel_puesto_dueno_id'],
                'empleos_nivel_global_foreign'
            )
                ->references(['id', 'dueno_id'])
                ->on('niveles_puesto')
                ->restrictOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        Schema::table('empleos', function (Blueprint $table) {
            $table->dropForeign('empleos_nivel_global_foreign');
        });

        Schema::table('empleos', function (Blueprint $table) {
            $table->dropColumn('nivel_puesto_dueno_id');
        });

        Schema::table('niveles_puesto', function (Blueprint $table) {
            $table->dropUnique('niveles_puesto_id_dueno_unique');
        });
    }
};
