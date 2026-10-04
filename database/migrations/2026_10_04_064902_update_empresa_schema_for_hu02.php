<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->string('encargado_empresa', 255)
                ->after('usuario_id');

            $table->string('sector', 100)
                ->after('razon_social');

            $table->string('descripcion', 250)
                ->nullable()
                ->change();
        });

        // El encargado es obligatorio y no puede contener solo espacios.
        DB::statement("
            ALTER TABLE empresas
            ADD CONSTRAINT chk_empresas_encargado_no_vacio
            CHECK (CHAR_LENGTH(TRIM(encargado_empresa)) > 0)
        ");

        // La razón social es obligatoria y no puede contener solo espacios.
        DB::statement("
            ALTER TABLE empresas
            ADD CONSTRAINT chk_empresas_razon_social_no_vacia
            CHECK (CHAR_LENGTH(TRIM(razon_social)) > 0)
        ");

        // Solo se permiten los sectores definidos para el sistema.
        DB::statement("
            ALTER TABLE empresas
            ADD CONSTRAINT chk_empresas_sector
            CHECK (
                sector IN (
                    'Salud y Medicina',
                    'Ciencias e Investigación',
                    'Tecnología e Informática',
                    'Educación',
                    'Administración y Negocios',
                    'Comunicación y Marketing',
                    'Diseño y Artes',
                    'Derecho',
                    'Ingeniería'
                )
            )
        ");

        // El RUC debe contener exactamente 11 dígitos numéricos.
        DB::statement("
            ALTER TABLE empresas
            ADD CONSTRAINT chk_empresas_ruc
            CHECK (
                REGEXP_LIKE(ruc, '^[0-9]{11}$')
            )
        ");
    }

    public function down(): void
    {
        DB::statement("
            ALTER TABLE empresas
            DROP CHECK chk_empresas_encargado_no_vacio
        ");

        DB::statement("
            ALTER TABLE empresas
            DROP CHECK chk_empresas_razon_social_no_vacia
        ");

        DB::statement("
            ALTER TABLE empresas
            DROP CHECK chk_empresas_sector
        ");

        DB::statement("
            ALTER TABLE empresas
            DROP CHECK chk_empresas_ruc
        ");

        Schema::table('empresas', function (Blueprint $table) {
            $table->text('descripcion')
                ->nullable()
                ->change();

            $table->dropColumn([
                'encargado_empresa',
                'sector',
            ]);
        });
    }
};
