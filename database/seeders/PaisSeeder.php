<?php

namespace Database\Seeders;

use App\Models\Pais;
use Illuminate\Database\Seeder;

class PaisSeeder extends Seeder
{
    public function run(): void
    {
        $paises = ['Perú', 'Argentina', 'Chile', 'Colombia', 'México'];

        foreach ($paises as $nombre) {
            Pais::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
