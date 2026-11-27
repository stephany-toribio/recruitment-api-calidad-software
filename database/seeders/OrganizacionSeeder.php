<?php

namespace Database\Seeders;

use App\Models\Organizacion;
use App\Models\Pais;
use Illuminate\Database\Seeder;

class OrganizacionSeeder extends Seeder
{
    public function run(): void
    {
        // Organizaciones de referencia para la experiencia laboral (no son Empresas del portal)
        $aprobadasPorPais = [
            'Perú' => [
                'Banco de Crédito del Perú',
                'Interbank',
                'Alicorp',
                'Belcorp',
                'Telefónica del Perú',
                'Clínica Internacional',
            ],
            'Argentina' => ['Mercado Libre', 'Globant'],
            'Chile'     => ['Falabella', 'LATAM Airlines'],
            'Colombia'  => ['Bancolombia', 'Rappi'],
            'México'    => ['Grupo Bimbo', 'FEMSA'],
        ];

        $paises = Pais::pluck('id', 'nombre');

        // Catálogo global (candidato_id NULL). El default de la tabla es inactivo: se marca activo explícitamente.
        // Las personalizadas no se siembran: las crea cada candidato al escribir una organización que no está en el catálogo.
        foreach ($aprobadasPorPais as $pais => $organizaciones) {
            foreach ($organizaciones as $nombre) {
                Organizacion::updateOrCreate(
                    ['nombre' => $nombre, 'pais_id' => $paises[$pais], 'candidato_id' => null],
                    ['estado' => 'activo']
                );
            }
        }
    }
}
