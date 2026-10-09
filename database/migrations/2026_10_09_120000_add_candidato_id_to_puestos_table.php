<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Valores personalizados de puesto (HU-01): el puesto que escribe un candidato es suyo y no pasa al catálogo global.
     *
     * - candidato_id: NULL = catálogo global; con valor = personalizado del candidato que lo escribió.
     * - estado: solo indica si el administrador lo tiene habilitado o deshabilitado.
     * - estado_catalogo (pendiente/aprobado) desaparece junto con su CHECK.
     * - Unicidad: nombre único entre los globales y por candidato. Como MySQL no compara NULL en un unique,
     *   dueno_id = COALESCE(candidato_id, 0) da un valor fijo (0) a los globales.
     *
     * Orden:
     * 1. Soltar el CHECK y el unique(nombre), que impiden activar o separar los pendientes.
     * 2. Agregar candidato_id.
     * 3. Datos: cada pendiente pasa a ser personalizado de quien lo usa. Si lo usan varios candidatos se
     *    crea una copia por candidato y se reasignan sus experiencias. Los que nadie usa se eliminan.
     * 4. Quitar estado_catalogo y crear dueno_id + unique(nombre, dueno_id).
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE puestos DROP CHECK chk_puestos_activo_solo_aprobado');
        }

        Schema::table('puestos', function (Blueprint $table) {
            $table->dropUnique('puestos_nombre_unique');

            // restrict: los candidatos no se eliminan (se suspende al usuario) y MySQL no permite
            // CASCADE / SET NULL en la base de una columna generada (dueno_id)
            $table->foreignId('candidato_id')->nullable()->after('id')
                ->constrained('candidatos')->restrictOnDelete();
        });

        $this->convertirPendientesEnPersonalizados();

        Schema::table('puestos', function (Blueprint $table) {
            $table->dropColumn('estado_catalogo');

            $table->unsignedBigInteger('dueno_id')->storedAs('COALESCE(candidato_id, 0)')->after('candidato_id');
            $table->unique(['nombre', 'dueno_id'], 'puestos_nombre_dueno_unique');
        });
    }

    /**
     * Reverse the migrations.
     * Los personalizados con el mismo nombre se fusionan (el global, si existe, o el de menor id) para
     * poder volver al unique(nombre), y quedan como pendientes e inactivos.
     */
    public function down(): void
    {
        Schema::table('puestos', function (Blueprint $table) {
            $table->dropUnique('puestos_nombre_dueno_unique');
            $table->dropColumn('dueno_id');
            $table->enum('estado_catalogo', ['pendiente', 'aprobado'])->default('pendiente')->after('estado');
        });

        $this->fusionarNombresRepetidos();

        DB::table('puestos')->whereNull('candidato_id')->update(['estado_catalogo' => 'aprobado']);
        DB::table('puestos')->whereNotNull('candidato_id')->update(['estado_catalogo' => 'pendiente', 'estado' => 'inactivo']);

        Schema::table('puestos', function (Blueprint $table) {
            $table->dropForeign(['candidato_id']);
            $table->dropColumn('candidato_id');
            $table->unique('nombre', 'puestos_nombre_unique');
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE puestos
                ADD CONSTRAINT chk_puestos_activo_solo_aprobado
                CHECK (estado = 'inactivo' OR estado_catalogo = 'aprobado')
            ");
        }
    }

    private function convertirPendientesEnPersonalizados(): void
    {
        DB::transaction(function () {
            $pendientes = DB::table('puestos')->where('estado_catalogo', 'pendiente')->orderBy('id')->get();

            foreach ($pendientes as $puesto) {
                $candidatos = DB::table('experiencias_laborales')
                    ->where('puesto_id', $puesto->id)
                    ->orderBy('id')
                    ->pluck('candidato_id')
                    ->unique()
                    ->values();

                if ($candidatos->isEmpty()) {
                    DB::table('puestos')->where('id', $puesto->id)->delete();
                    continue;
                }

                // El primero que lo usó se queda con la fila original
                DB::table('puestos')->where('id', $puesto->id)->update([
                    'candidato_id' => $candidatos->first(),
                    'estado'       => 'activo',
                ]);

                foreach ($candidatos->slice(1) as $candidatoId) {
                    $copiaId = DB::table('puestos')->insertGetId([
                        'nombre'          => $puesto->nombre,
                        'candidato_id'    => $candidatoId,
                        'estado'          => 'activo',
                        'estado_catalogo' => 'pendiente',
                        'created_at'      => $puesto->created_at,
                        'updated_at'      => now(),
                    ]);

                    DB::table('experiencias_laborales')
                        ->where('puesto_id', $puesto->id)
                        ->where('candidato_id', $candidatoId)
                        ->update(['puesto_id' => $copiaId]);
                }
            }
        });
    }

    private function fusionarNombresRepetidos(): void
    {
        DB::transaction(function () {
            $grupos = DB::table('puestos')
                ->orderByRaw('candidato_id IS NOT NULL') // el global primero
                ->orderBy('id')
                ->get(['id', 'nombre', 'candidato_id'])
                ->groupBy(fn ($puesto) => mb_strtolower(trim($puesto->nombre)));

            foreach ($grupos as $puestos) {
                $conservado = $puestos->first()->id;
                $repetidos = $puestos->slice(1)->pluck('id');

                if ($repetidos->isEmpty()) {
                    continue;
                }

                DB::table('experiencias_laborales')->whereIn('puesto_id', $repetidos)->update(['puesto_id' => $conservado]);
                DB::table('puestos')->whereIn('id', $repetidos)->delete();
            }
        });
    }
};
