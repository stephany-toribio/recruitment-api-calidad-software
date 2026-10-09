<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Valores personalizados de organización (HU-01), igual que los puestos: la organización que escribe un candidato
     * es suya y no pasa al catálogo global. A diferencia del puesto, pertenece a un país.
     *
     * - candidato_id: NULL = catálogo global; con valor = personalizada del candidato que la escribió.
     * - estado: solo indica si el administrador la tiene habilitada o deshabilitada.
     * - estado_catalogo (pendiente/aprobado) desaparece junto con su CHECK.
     * - Unicidad: nombre único por país entre las globales y por país y candidato entre las personalizadas
     *   (dueno_id = COALESCE(candidato_id, 0), porque MySQL no compara NULL en un unique).
     * - Se mantiene unique(id, pais_id): es el destino de la FK compuesta de experiencias_laborales.
     *
     * Orden:
     * 1. Soltar el CHECK y el unique(nombre, pais_id), que impiden activar o separar las pendientes.
     * 2. Agregar candidato_id.
     * 3. Datos: cada pendiente pasa a ser personalizada de quien la usa. Con el modelo anterior, varios candidatos
     *    que escribían el mismo nombre en el mismo país compartían una fila: se crea una copia (mismo país) por
     *    candidato y se reasignan sus experiencias. Las que nadie usa se eliminan.
     * 4. Quitar estado_catalogo, crear dueno_id + unique(nombre, pais_id, dueno_id) y el CHECK de nombre no vacío.
     */
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

    /**
     * Reverse the migrations.
     * Las organizaciones con el mismo nombre y país se fusionan (la global, si existe, o la de menor id) para
     * poder volver al unique(nombre, pais_id), y las personalizadas quedan como pendientes e inactivas.
     */
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
