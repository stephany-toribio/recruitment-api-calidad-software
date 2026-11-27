<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TIPO = 'Sin estudios formales';

    private const FK_PAIS = 'candidato_estudios_pais_id_foreign';
    private const FK_INSTITUCION_PAIS = 'fk_estudio_institucion_pais';

    /**
     * Tipo de estudio "Sin estudios formales" (HU-01): para quien solo llevó cursos externos.
     * Solo registra el área de estudio (la necesita la regla de habilidades técnicas), así que
     * país, estado y fecha de inicio pasan a ser opcionales en candidato_estudios.
     * Qué campos lleva cada tipo lo valida ReglasPerfilCandidato.
     *
     * Orden:
     * 1. Agregar el tipo, solo si la tabla ya tiene tipos: en una base nueva lo crea TipoEstudioSeeder
     *    después de los demás, para no alterar los ids de los tipos existentes.
     * 2. Soltar las FK que usan pais_id: MySQL no permite modificar una columna que está en una FK (error 1832).
     * 3. Hacer nullable pais_id, estado y fecha_inicio.
     * 4. Volver a crear las FK (sus índices no se tocan).
     *
     * Cada paso revisa si ya se hizo: MySQL no revierte cambios de estructura, así que si la
     * migración se corta a la mitad se puede volver a ejecutar y continúa donde quedó.
     * Los CHECK de fechas y ciclo ya aceptan NULL.
     */
    public function up(): void
    {
        if (DB::table('tipos_estudio')->exists() && ! DB::table('tipos_estudio')->where('nombre', self::TIPO)->exists()) {
            DB::table('tipos_estudio')->insert([
                'nombre'     => self::TIPO,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $this->soltarFksDePais();

        Schema::table('candidato_estudios', function (Blueprint $table) {
            $table->unsignedBigInteger('pais_id')->nullable()->change();
            $table->enum('estado', ['finalizado', 'en_curso', 'incompleto'])->nullable()->change();
            $table->date('fecha_inicio')->nullable()->change();
        });

        $this->crearFksDePais();
    }

    /**
     * Reverse the migrations.
     * Solo si no hay estudios "Sin estudios formales": esos no tienen país, estado ni fecha y quedarían inválidos.
     */
    public function down(): void
    {
        $tipoId = DB::table('tipos_estudio')->where('nombre', self::TIPO)->value('id');

        if ($tipoId && DB::table('candidato_estudios')->where('tipo_estudio_id', $tipoId)->exists()) {
            throw new RuntimeException(
                'No se puede revertir: hay estudios de tipo "' . self::TIPO . '" sin país, estado ni fecha de inicio. '
                . 'Elimínalos o complétalos antes de hacer rollback.'
            );
        }

        $this->soltarFksDePais();

        Schema::table('candidato_estudios', function (Blueprint $table) {
            $table->unsignedBigInteger('pais_id')->nullable(false)->change();
            $table->enum('estado', ['finalizado', 'en_curso', 'incompleto'])->nullable(false)->change();
            $table->date('fecha_inicio')->nullable(false)->change();
        });

        $this->crearFksDePais();

        if ($tipoId) {
            DB::table('tipos_estudio')->where('id', $tipoId)->delete();
        }
    }

    private function soltarFksDePais(): void
    {
        foreach ([self::FK_PAIS, self::FK_INSTITUCION_PAIS] as $fk) {
            if ($this->tieneFk($fk)) {
                Schema::table('candidato_estudios', function (Blueprint $table) use ($fk) {
                    $table->dropForeign($fk);
                });
            }
        }
    }

    private function crearFksDePais(): void
    {
        if (! $this->tieneFk(self::FK_PAIS)) {
            Schema::table('candidato_estudios', function (Blueprint $table) {
                $table->foreign('pais_id', self::FK_PAIS)->references('id')->on('paises')->restrictOnDelete();
            });
        }

        // La institución debe ser del país del estudio (con institución NULL la FK no se evalúa)
        if (! $this->tieneFk(self::FK_INSTITUCION_PAIS)) {
            Schema::table('candidato_estudios', function (Blueprint $table) {
                $table->foreign(['institucion_id', 'pais_id'], self::FK_INSTITUCION_PAIS)
                    ->references(['id', 'pais_id'])->on('instituciones');
            });
        }
    }

    private function tieneFk(string $nombre): bool
    {
        return collect(Schema::getForeignKeys('candidato_estudios'))->contains('name', $nombre);
    }
};
