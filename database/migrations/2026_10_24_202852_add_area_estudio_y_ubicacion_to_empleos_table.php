<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleos', function (Blueprint $table) {
            $table->foreignId('area_estudio_id')
                ->nullable()
                ->constrained('areas_estudio')
                ->restrictOnDelete();

            $table->string('ubicacion', 255)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('empleos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_estudio_id');
            $table->dropColumn('ubicacion');
        });
    }
};
