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

        // Los defaults de la tabla son inactivo/pendiente: el catálogo base se marca explícitamente
        foreach ($aprobadasPorPais as $pais => $organizaciones) {
            foreach ($organizaciones as $nombre) {
                Organizacion::updateOrCreate(
                    ['nombre' => $nombre, 'pais_id' => $paises[$pais]],
                    ['estado' => 'activo', 'estado_catalogo' => 'aprobado']
                );
            }
        }

        // Ejemplo de organización propuesta por un candidato, para probar la moderación del admin
        Organizacion::updateOrCreate(
            ['nombre' => 'Consultora Andina SAC', 'pais_id' => $paises['Perú']],
            ['estado' => 'inactivo', 'estado_catalogo' => 'pendiente']
        );
    }
}
