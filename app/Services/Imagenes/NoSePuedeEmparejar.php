<?php

declare(strict_types=1);

namespace App\Services\Imagenes;

use RuntimeException;

/** La foto no se puede emparejar; el mensaje dice por qué, para mostrárselo al admin. */
final class NoSePuedeEmparejar extends RuntimeException
{
}
