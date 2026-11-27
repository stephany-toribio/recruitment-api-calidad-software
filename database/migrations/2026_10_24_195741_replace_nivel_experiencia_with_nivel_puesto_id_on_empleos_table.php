<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const EQUIVALENCIAS = [
        'junior' => 'Junior',
        'semi_senior' => 'Semi Senior',
        'senior' => 'Senior',
    ];

    public function up(): void
    {
        $niveles = $this->resolverNiveles();

        $hayNivelDesconocido = DB::table('empleos')
            ->where(function ($query) {
                $query->whereNull('nivel_experiencia')
                    ->orWhereNotIn(
                        'nivel_experiencia',
                        array_keys(self::EQUIVALENCIAS)
                    );
            })
            ->exists();

        if ($hayNivelDesconocido) {
            throw new RuntimeException(
                'Existen vacantes con niveles sin equivalencia. No se modificó la estructura.'
            );
        }

        Schema::table('empleos', function (Blueprint $table) {
            $table->unsignedBigInteger('nivel_puesto_id')->nullable();
        });

        foreach ($niveles as $codigo => $id) {
            DB::table('empleos')
                ->where('nivel_experiencia', $codigo)
                ->update(['nivel_puesto_id' => $id]);
        }

        if (DB::table('empleos')->whereNull('nivel_puesto_id')->exists()) {
            throw new RuntimeException(
                'Hay vacantes pendientes de convertir. No se retiró nivel_experiencia.'
            );
        }

        Schema::table('empleos', function (Blueprint $table) {
            $table->unsignedBigInteger('nivel_puesto_id')
                ->nullable(false)
                ->change();
        });

        Schema::table('empleos', function (Blueprint $table) {
            $table->foreign('nivel_puesto_id')
                ->references('id')
                ->on('niveles_puesto')
                ->restrictOnDelete();
        });

        Schema::table('empleos', function (Blueprint $table) {
            $table->dropColumn('nivel_experiencia');
        });
    }

    public function down(): void
    {
        $niveles = $this->resolverNiveles();

        $hayNivelNoReversible = DB::table('empleos')
            ->where(function ($query) use ($niveles) {
                $query->whereNull('nivel_puesto_id')
                    ->orWhereNotIn(
                        'nivel_puesto_id',
                        array_values($niveles)
                    );
            })
            ->exists();

        if ($hayNivelNoReversible) {
            throw new RuntimeException(
                'No se puede revertir: existen vacantes con niveles que el esquema anterior no admite.'
            );
        }

        Schema::table('empleos', function (Blueprint $table) {
            $table->enum(
                'nivel_experiencia',
                array_keys(self::EQUIVALENCIAS)
            )->nullable();
        });

        foreach ($niveles as $codigo => $id) {
            DB::table('empleos')
                ->where('nivel_puesto_id', $id)
                ->update(['nivel_experiencia' => $codigo]);
        }

        if (DB::table('empleos')->whereNull('nivel_experiencia')->exists()) {
            throw new RuntimeException(
                'Hay vacantes pendientes de revertir. No se retiró nivel_puesto_id.'
            );
        }

        Schema::table('empleos', function (Blueprint $table) {
            $table->enum(
                'nivel_experiencia',
                array_keys(self::EQUIVALENCIAS)
            )->nullable(false)->change();
        });

        Schema::table('empleos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('nivel_puesto_id');
        });
    }

    private function resolverNiveles(): array
    {
        $resultado = [];

        foreach (self::EQUIVALENCIAS as $codigo => $nombre) {
            $ids = DB::table('niveles_puesto')
                ->whereNull('candidato_id')
                ->where('nombre', $nombre)
                ->pluck('id');

            if ($ids->count() !== 1) {
                throw new RuntimeException(
                    "Debe existir exactamente un nivel global equivalente a {$nombre}."
                );
            }

            $resultado[$codigo] = (int) $ids->first();
        }

        return $resultado;
    }
};
