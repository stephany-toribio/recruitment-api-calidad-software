<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class PasswordSegura implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value) || strlen($value) < 8) {
            $fail('La contraseña debe tener al menos 8 caracteres.');
            return;
        }

        if (!preg_match('/[A-Z]/', $value)) {
            $fail('La contraseña debe contener al menos una letra mayúscula.');
            return;
        }

        if (!preg_match('/[0-9]/', $value)) {
            $fail('La contraseña debe contener al menos un número.');
            return;
        }

        if (!preg_match('/[@#$%&]/', $value)) {
            $fail('La contraseña debe contener al menos uno de estos caracteres: @ # $ % &.');
        }
    }
}