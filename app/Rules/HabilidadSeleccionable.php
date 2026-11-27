<?php

namespace App\Rules;

use App\Models\Habilidad;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class HabilidadSeleccionable implements ValidationRule
{
    public function validate(
        string $attribute,
        mixed $value,
        Closure $fail
    ): void {
        $seleccionable = Habilidad::query()
            ->seleccionables()
            ->whereKey($value)
            ->exists();

        if (! $seleccionable) {
            $fail(
                'La habilidad seleccionada no está disponible en el catálogo.'
            );
        }
    }
}
