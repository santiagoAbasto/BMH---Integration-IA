<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Carbon;

/**
 * El año en curso en Argentina: así el «© Copyright» del footer (y cualquier texto con el año) se actualiza
 * solo cada 1 de enero, sin tocar el código. Se fija la zona de Buenos Aires porque la app corre en UTC y,
 * si no, el 31 de diciembre desde las 21 hs ya mostraría el año siguiente.
 */
final class Anio
{
    public const ZONA = 'America/Argentina/Buenos_Aires';

    public function actual(): int
    {
        return Carbon::now(self::ZONA)->year;
    }
}
