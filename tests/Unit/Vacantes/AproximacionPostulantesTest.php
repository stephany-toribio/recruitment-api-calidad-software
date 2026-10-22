<?php

declare(strict_types=1);

namespace Tests\Unit\Vacantes;

use App\Services\Vacantes\AproximacionPostulantes;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class AproximacionPostulantesTest extends TestCase
{
    #[DataProvider('casosDeRangos')]
    public function test_devuelve_el_rango_acordado(
        int $cantidad,
        string $esperado
    ): void {
        $this->assertSame(
            $esperado,
            AproximacionPostulantes::texto($cantidad)
        );
    }

    public static function casosDeRangos(): array
    {
        return [
            'cero' => [0, 'Menos de 10 postulantes'],
            'uno' => [1, 'Menos de 10 postulantes'],
            'antes de diez' => [9, 'Menos de 10 postulantes'],
            'diez' => [10, 'Entre 10 y 24 postulantes'],
            'antes de veinticinco' => [24, 'Entre 10 y 24 postulantes'],
            'veinticinco' => [25, 'Entre 25 y 49 postulantes'],
            'antes de cincuenta' => [49, 'Entre 25 y 49 postulantes'],
            'cincuenta' => [50, 'Entre 50 y 99 postulantes'],
            'antes de cien' => [99, 'Entre 50 y 99 postulantes'],
            'cien' => [100, '100 o más postulantes'],
            'mas de cien' => [101, '100 o más postulantes'],
            'entero maximo' => [PHP_INT_MAX, '100 o más postulantes'],
        ];
    }

    public function test_rechaza_cantidades_negativas(): void
    {
        $this->expectException(InvalidArgumentException::class);

        AproximacionPostulantes::texto(-1);
    }
}
