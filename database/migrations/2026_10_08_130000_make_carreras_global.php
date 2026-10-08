<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Las carreras pasan a ser globales: existen en todos los países y dependen solo del tipo de estudio.
     * El país se mantiene en candidato_estudios (dónde estudió) y sigue validando la institución.
     *
     * Orden:
     * 1. Soltar la FK compuesta (carrera, tipo, país) de candidato_estudios.
     * 2. Datos: cada carrera repetida por país se fusiona en la de menor id con el mismo (nombre, tipo);
     *    los estudios se reasignan a esa carrera y las repetidas se eliminan.
     * 3. Quitar carreras.pais_id y crear los únicos (nombre, tipo) e (id, tipo).
     * 4. Crear un índice propio (carrera, tipo) y la FK compuesta nueva sobre él.
     * 5. Borrar el índice que dejó la FK anterior.
     *
     * Cada paso revisa si ya se hizo: MySQL no revierte cambios de estructura, así que si la
     * migración se corta a la mitad se puede volver a ejecutar y continúa donde quedó.
     */
    public function up(): void
    {
        if ($this->tieneFk('candidato_estudios', 'fk_estudio_carrera_tipo_pais')) {
            Schema::table('candidato_estudios', function (Blueprint $table) {
                $table->dropForeign('fk_estudio_carrera_tipo_pais');
            });
        }

        $this->fusionarCarrerasRepetidas();

        if (Schema::hasColumn('carreras', 'pais_id')) {
            Schema::table('carreras', function (Blueprint $table) {
                $table->dropUnique('carreras_id_tipo_pais_unique');
                $table->dropUnique('carreras_nombre_tipo_pais_unique');
                $table->dropForeign(['pais_id']);
                $table->dropColumn('pais_id');

                $table->unique(['nombre', 'tipo_estudio_id'], 'carreras_nombre_tipo_unique');
                $table->unique(['id', 'tipo_estudio_id'], 'carreras_id_tipo_unique'); // destino de la FK compuesta
            });
        }

        // Índice propio para la FK nueva. Sin él, MySQL reutiliza el índice viejo (carrera, tipo, país),
        // que empieza con las mismas columnas, y después no deja borrarlo (error 1553).
        if (! Schema::hasIndex('candidato_estudios', 'idx_estudio_carrera_tipo')) {
            Schema::table('candidato_estudios', function (Blueprint $table) {
                $table->index(['carrera_id', 'tipo_estudio_id'], 'idx_estudio_carrera_tipo');
            });
        }

        if (! $this->tieneFk('candidato_estudios', 'fk_estudio_carrera_tipo')) {
            Schema::table('candidato_estudios', function (Blueprint $table) {
                // La carrera debe ser del mismo tipo de estudio que el estudio
                $table->foreign(['carrera_id', 'tipo_estudio_id'], 'fk_estudio_carrera_tipo')
                    ->references(['id', 'tipo_estudio_id'])->on('carreras');
            });
        }

        // Índice que dejó la FK anterior: ahora las FK de carrera usan idx_estudio_carrera_tipo
        if (Schema::hasIndex('candidato_estudios', 'fk_estudio_carrera_tipo_pais')) {
            Schema::table('candidato_estudios', function (Blueprint $table) {
                $table->dropIndex('fk_estudio_carrera_tipo_pais');
            });
        }
    }

    /**
     * Reverse the migrations.
     * Revierte la estructura. Las carreras fusionadas no vuelven: la columna regresa como nullable
     * y hay que volver a correr el seeder para tener las carreras por país.
     */
    public function down(): void
    {
        Schema::table('carreras', function (Blueprint $table) {
            $table->foreignId('pais_id')->nullable()->after('tipo_estudio_id')
                ->constrained('paises')->restrictOnDelete();
            $table->unique(['nombre', 'tipo_estudio_id', 'pais_id'], 'carreras_nombre_tipo_pais_unique');
            $table->unique(['id', 'tipo_estudio_id', 'pais_id'], 'carreras_id_tipo_pais_unique');
        });

        Schema::table('candidato_estudios', function (Blueprint $table) {
            $table->foreign(['carrera_id', 'tipo_estudio_id', 'pais_id'], 'fk_estudio_carrera_tipo_pais')
                ->references(['id', 'tipo_estudio_id', 'pais_id'])->on('carreras');
            $table->dropForeign('fk_estudio_carrera_tipo');
        });

        foreach (['idx_estudio_carrera_tipo', 'fk_estudio_carrera_tipo'] as $indice) {
            if (Schema::hasIndex('candidato_estudios', $indice)) {
                Schema::table('candidato_estudios', function (Blueprint $table) use ($indice) {
                    $table->dropIndex($indice);
                });
            }
        }

        Schema::table('carreras', function (Blueprint $table) {
            $table->dropUnique('carreras_id_tipo_unique');
            $table->dropUnique('carreras_nombre_tipo_unique');
        });
    }

    private function tieneFk(string $tabla, string $nombre): bool
    {
        return collect(Schema::getForeignKeys($tabla))->contains('name', $nombre);
    }

    /**
     * Fusiona las carreras con el mismo (nombre, tipo) en la de menor id.
     * Antes de cambiar nada verifica que la reasignación no duplique un estudio del candidato
     * (único candidato_estudio_unique: candidato, tipo, institución, carrera).
     */
    private function fusionarCarrerasRepetidas(): void
    {
        // id de cada carrera repetida => id de la carrera que se conserva
        $reemplazos = [];
        $conservadas = [];

        foreach (DB::table('carreras')->orderBy('id')->get(['id', 'nombre', 'tipo_estudio_id']) as $carrera) {
            $clave = mb_strtolower(trim($carrera->nombre)) . '|' . $carrera->tipo_estudio_id;

            if (isset($conservadas[$clave])) {
                $reemplazos[$carrera->id] = $conservadas[$clave];
            } else {
                $conservadas[$clave] = $carrera->id;
            }
        }

        if ($reemplazos === []) {
            return;
        }

        $estudios = DB::table('candidato_estudios')
            ->whereNotNull('carrera_id')
            ->get(['id', 'candidato_id', 'tipo_estudio_id', 'institucion_id', 'carrera_id']);

        $vistos = [];
        foreach ($estudios as $estudio) {
            $carreraFinal = $reemplazos[$estudio->carrera_id] ?? $estudio->carrera_id;
            $clave = "{$estudio->candidato_id}|{$estudio->tipo_estudio_id}|{$estudio->institucion_id}|{$carreraFinal}";

            if (isset($vistos[$clave])) {
                throw new RuntimeException(
                    "No se pueden fusionar las carreras: los estudios {$vistos[$clave]} y {$estudio->id} "
                    . "quedarían repetidos (mismo candidato, tipo, institución y carrera). Corrígelos antes de migrar."
                );
            }
            $vistos[$clave] = $estudio->id;
        }

        DB::transaction(function () use ($reemplazos) {
            foreach ($reemplazos as $repetida => $conservada) {
                DB::table('candidato_estudios')->where('carrera_id', $repetida)->update(['carrera_id' => $conservada]);
            }

            DB::table('carreras')->whereIn('id', array_keys($reemplazos))->delete();
        });
    }
};
