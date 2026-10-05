<?php

namespace Database\Seeders;

use App\Models\Idioma;
use Illuminate\Database\Seeder;

class IdiomaSeeder extends Seeder
{
    public function run(): void
    {
        $idiomas = ['Inglés', 'Español', 'Portugués', 'Francés', 'Alemán', 'Italiano'];

        foreach ($idiomas as $nombre) {
            Idioma::firstOrCreate(['nombre' => $nombre]);
        }
    }
}
