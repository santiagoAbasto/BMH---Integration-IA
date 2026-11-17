<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Texto libre de un formulario público sin HTML ni enlaces.
 *
 * Los bots de spam («$3,222 credit available») completan cualquier formulario con un <a href> en cada campo: en
 * un nombre, una dirección o una localidad reales nunca hay etiquetas, URLs ni dominios.
 */
final class SinEnlaces implements ValidationRule
{
    private const PATRON = '~[<>]|https?:|://|\bwww\.|\bhref\b|\[url~i';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && preg_match(self::PATRON, $value) === 1) {
            $fail('El campo :attribute no puede llevar enlaces ni etiquetas.');
        }
    }
}
