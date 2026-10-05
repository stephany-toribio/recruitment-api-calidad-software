<?php

namespace Database\Seeders;

use App\Models\TipoEstudio;
use Illuminate\Database\Seeder;

class TipoEstudioSeeder extends Seeder
{
    public function run(): void
    {
        $tipos = ['Universitario', 'Terciario', 'Posgrado', 'Master', 'Doctorado', 'Secundario'];

        foreach ($tipos as $nombre) {
            TipoEstudio::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
