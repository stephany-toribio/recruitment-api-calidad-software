<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CHECKS = [
        'chk_empleos_titulo',
        'chk_empleos_salarios',
        'chk_empleos_vigencia',
        'chk_empleos_numero_vacantes',
    ];

    /**
     * Adapta empleos al flujo de EP-02 (HU-03 y HU-14). Base de vacantes del plan de Luis.
     * - Vigencia por fecha de inicio y de fin.
     * - estado_empleo pasa a ser la disponibilidad que decide la empresa: activa, pausada o finalizada.
     *   Aprobación y pago no van aquí: viven en revisiones_vacante y pagos_vacante.
     * - Eliminación lógica con deleted_at.
     * - Se quitan fecha_publicacion y fecha_cierre: la publicación sale del pago exitoso y el cierre,
     *   de fecha_fin_vigencia. Así no quedan dos campos con el mismo significado.
     */
    public function up(): void
    {
        // 'cerrado' no tiene un estado nuevo acordado: mejor detener la migración que adivinar
        if (DB::table('empleos')->where('estado_empleo', 'cerrado')->exists()) {
            throw new RuntimeException(
                'Hay vacantes con estado "cerrado". Definan a qué estado nuevo corresponden antes de migrar.'
            );
        }

        // Paso intermedio: el enum admite los valores viejos y los nuevos para convertir las filas
        Schema::table('empleos', function (Blueprint $table) {
            $table->enum('estado_empleo', ['activo', 'cerrado', 'finalizado', 'activa', 'pausada', 'finalizada'])
                ->default('activa')
                ->change();
        });

        DB::table('empleos')->where('estado_empleo', 'activo')->update(['estado_empleo' => 'activa']);
        DB::table('empleos')->where('estado_empleo', 'finalizado')->update(['estado_empleo' => 'finalizada']);

        Schema::table('empleos', function (Blueprint $table) {
            $table->enum('estado_empleo', ['activa', 'pausada', 'finalizada'])
                ->default('activa')
                ->change();

            $table->date('fecha_inicio_vigencia')->after('salario_max');
            $table->date('fecha_fin_vigencia')->after('fecha_inicio_vigencia');

            $table->dropColumn(['fecha_publicacion', 'fecha_cierre']);

            $table->softDeletes();
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement("
                ALTER TABLE empleos
                ADD CONSTRAINT chk_empleos_titulo
                CHECK (CHAR_LENGTH(TRIM(titulo)) > 0)
            ");

            DB::statement("
                ALTER TABLE empleos
                ADD CONSTRAINT chk_empleos_salarios
                CHECK (salario_min > 0 AND salario_max >= salario_min)
            ");

            DB::statement("
                ALTER TABLE empleos
                ADD CONSTRAINT chk_empleos_vigencia
                CHECK (fecha_fin_vigencia > fecha_inicio_vigencia)
            ");

            // numero_vacantes = número máximo de contrataciones (en la API: max_contrataciones)
            DB::statement("
                ALTER TABLE empleos
                ADD CONSTRAINT chk_empleos_numero_vacantes
                CHECK (numero_vacantes >= 1)
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Los CHECK se quitan antes, porque MySQL no deja borrar columnas que un CHECK usa
        if (DB::getDriverName() === 'mysql') {
            foreach (self::CHECKS as $check) {
                DB::statement("ALTER TABLE empleos DROP CHECK {$check}");
            }
        }

        Schema::table('empleos', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['fecha_inicio_vigencia', 'fecha_fin_vigencia']);

            $table->timestamp('fecha_publicacion')->useCurrent()->after('estado_empleo');
            $table->dateTime('fecha_cierre')->nullable()->after('fecha_publicacion');

            $table->enum('estado_empleo', ['activo', 'cerrado', 'finalizado', 'activa', 'pausada', 'finalizada'])
                ->default('activo')
                ->change();
        });

        DB::table('empleos')->whereIn('estado_empleo', ['activa', 'pausada'])->update(['estado_empleo' => 'activo']);
        DB::table('empleos')->where('estado_empleo', 'finalizada')->update(['estado_empleo' => 'finalizado']);

        Schema::table('empleos', function (Blueprint $table) {
            $table->enum('estado_empleo', ['activo', 'cerrado', 'finalizado'])
                ->default('activo')
                ->change();
        });
    }
};
