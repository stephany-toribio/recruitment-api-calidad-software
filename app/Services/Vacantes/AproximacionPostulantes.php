<?php

declare(strict_types=1);

namespace App\Services\Vacantes;

use InvalidArgumentException;

final class AproximacionPostulantes
{
    public static function texto(int $cantidad): string
    {
        if ($cantidad < 0) {
            throw new InvalidArgumentException(
                'La cantidad de postulantes no puede ser negativa.'
            );
        }

        return match (true) {
            $cantidad < 10 => 'Menos de 10 postulantes',
            $cantidad < 25 => 'Entre 10 y 24 postulantes',
            $cantidad < 50 => 'Entre 25 y 49 postulantes',
            $cantidad < 100 => 'Entre 50 y 99 postulantes',
            default => '100 o más postulantes',
        };
    }
}
