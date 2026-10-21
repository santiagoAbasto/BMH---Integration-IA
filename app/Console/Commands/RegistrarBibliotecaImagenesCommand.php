<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\ImagenBiblioteca;
use App\Services\Imagenes\BibliotecaImagenes;
use Illuminate\Console\Command;

/**
 * Da código a las imágenes de productos que todavía no lo tienen: la
 * principal recibe el código del producto y las demás CODIGO-2, CODIGO-3.
 * Se puede correr de nuevo sin efecto (la biblioteca también lo hace sola
 * cada vez que se abre).
 */
class RegistrarBibliotecaImagenesCommand extends Command
{
    protected $signature = 'imagenes:registrar-biblioteca';

    protected $description = 'Da código en la biblioteca a las imágenes de productos que no lo tienen';

    public function handle(BibliotecaImagenes $biblioteca): int
    {
        $antes = ImagenBiblioteca::query()->count();
        $resultado = $biblioteca->sincronizar();

        $this->info("Registradas: {$resultado['registradas']} · ya estaban: {$antes} · total en la biblioteca: ".ImagenBiblioteca::query()->count());
        if ($resultado['sin_archivo'] > 0) {
            $this->warn("{$resultado['sin_archivo']} imágenes de productos no están en el disco y no se registraron.");
        }

        return self::SUCCESS;
    }
}
