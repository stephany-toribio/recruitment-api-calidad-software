<?php

namespace Database\Seeders;

use App\Models\TipoEstudio;
use Illuminate\Database\Seeder;

class TipoEstudioSeeder extends Seeder
{
    public function run(): void
    {
        // "Sin estudios formales" va al final para no cambiar los ids de los demás tipos
        $tipos = ['Universitario', 'Terciario', 'Posgrado', 'Master', 'Doctorado', 'Secundario', 'Sin estudios formales'];

        foreach ($tipos as $nombre) {
            TipoEstudio::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
