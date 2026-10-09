<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE organizaciones DROP CHECK chk_organizaciones_activo_solo_aprobado');
        }

        Schema::table('organizaciones', function (Blueprint $table) {
            $table->dropUnique('organizaciones_nombre_pais_id_unique');

            // restrict: los candidatos no se eliminan (se suspende al usuario) y MySQL no permite
            // CASCADE / SET NULL en la base de una columna generada (dueno_id)
            $table->foreignId('candidato_id')->nullable()->after('id')
                ->constrained('candidatos')->restrictOnDelete();
        });

        $this->convertirPendientesEnPersonalizadas();

        Schema::table('organizaciones', function (Blueprint $table) {
            $table->dropColumn('estado_catalogo');

            $table->unsignedBigInteger('dueno_id')->storedAs('COALESCE(candidato_id, 0)')->after('candidato_id');
            $table->unique(['nombre', 'pais_id', 'dueno_id'], 'organizaciones_nombre_pais_dueno_unique');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE organizaciones
                ADD CONSTRAINT chk_organizaciones_nombre
                CHECK (CHAR_LENGTH(TRIM(nombre)) > 0)
            ");
        }
    }

    
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE organizaciones DROP CHECK chk_organizaciones_nombre');
        }

        Schema::table('organizaciones', function (Blueprint $table) {
            $table->dropUnique('organizaciones_nombre_pais_dueno_unique');
            $table->dropColumn('dueno_id');
            $table->enum('estado_catalogo', ['pendiente', 'aprobado'])->default('pendiente')->after('estado');
        });

        $this->fusionarNombresRepetidos();

        DB::table('organizaciones')->whereNull('candidato_id')->update(['estado_catalogo' => 'aprobado']);
        DB::table('organizaciones')->whereNotNull('candidato_id')->update(['estado_catalogo' => 'pendiente', 'estado' => 'inactivo']);

        Schema::table('organizaciones', function (Blueprint $table) {
            $table->dropForeign(['candidato_id']);
            $table->dropColumn('candidato_id');
            $table->unique(['nombre', 'pais_id'], 'organizaciones_nombre_pais_id_unique');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE organizaciones
                ADD CONSTRAINT chk_organizaciones_activo_solo_aprobado
                CHECK (estado = 'inactivo' OR estado_catalogo = 'aprobado')
            ");
        }
    }

    private function convertirPendientesEnPersonalizadas(): void
    {
        DB::transaction(function () {
            $pendientes = DB::table('organizaciones')->where('estado_catalogo', 'pendiente')->orderBy('id')->get();

            foreach ($pendientes as $organizacion) {
                $candidatos = DB::table('experiencias_laborales')
                    ->where('organizacion_id', $organizacion->id)
                    ->orderBy('id')
                    ->pluck('candidato_id')
                    ->unique()
                    ->values();

                if ($candidatos->isEmpty()) {
                    DB::table('organizaciones')->where('id', $organizacion->id)->delete();
                    continue;
                }

                // El primero que la usó se queda con la fila original
                DB::table('organizaciones')->where('id', $organizacion->id)->update([
                    'candidato_id' => $candidatos->first(),
                    'estado'       => 'activo',
                ]);

                // Mismo país: la FK compuesta (organizacion_id, pais_id) de la experiencia lo exige
                foreach ($candidatos->slice(1) as $candidatoId) {
                    $copiaId = DB::table('organizaciones')->insertGetId([
                        'nombre'          => $organizacion->nombre,
                        'pais_id'         => $organizacion->pais_id,
                        'candidato_id'    => $candidatoId,
                        'estado'          => 'activo',
                        'estado_catalogo' => 'pendiente',
                        'created_at'      => $organizacion->created_at,
                        'updated_at'      => now(),
                    ]);

                    DB::table('experiencias_laborales')
                        ->where('organizacion_id', $organizacion->id)
                        ->where('candidato_id', $candidatoId)
                        ->update(['organizacion_id' => $copiaId]);
                }
            }
        });
    }

    private function fusionarNombresRepetidos(): void
    {
        DB::transaction(function () {
            $grupos = DB::table('organizaciones')
                ->orderByRaw('candidato_id IS NOT NULL') // la global primero
                ->orderBy('id')
                ->get(['id', 'nombre', 'pais_id', 'candidato_id'])
                ->groupBy(fn ($organizacion) => mb_strtolower(trim($organizacion->nombre)) . '|' . $organizacion->pais_id);

            foreach ($grupos as $organizaciones) {
                $conservada = $organizaciones->first()->id;
                $repetidas = $organizaciones->slice(1)->pluck('id');

                if ($repetidas->isEmpty()) {
                    continue;
                }

                DB::table('experiencias_laborales')->whereIn('organizacion_id', $repetidas)->update(['organizacion_id' => $conservada]);
                DB::table('organizaciones')->whereIn('id', $repetidas)->delete();
            }
        });
    }
};
