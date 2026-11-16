<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Textos de la página Nosotros (fila única). Lo que usa el rediseño:
 * - Historia: `historia_titulo` (vacío → el del Figma) e `info` (texto enriquecido). La foto es la imagen del sector
 *   `nosotros-portada` (tabla imagenes).
 * - «Lo que nos mueve»: `valores_titulo` y las tres tarjetas: `mision` (Misión), `vision` (Objetivos) y `valores`
 *   (Servicios). Los nombres de columna vienen del sitio viejo; una tarjeta vacía no se muestra.
 * El resto de las columnas (banner, home, kovea) son del sitio anterior y ya no se muestran.
 */
class Nosotros extends Model
{
    use HasFactory;

    protected $table = 'nosotros_contenido';

    public const HISTORIA_TITULO = 'Compromiso y dedicación, todos los días';

    public const VALORES_TITULO = 'Lo que nos mueve';

    public const IMAGEN_DE_FABRICA = 'img/rediseno/planta-bmh.webp';

    /** Las tres tarjetas: columna => [título, ícono]. */
    public const TARJETAS = [
        'mision' => ['Misión', 'mision'],
        'vision' => ['Objetivos', 'objetivos'],
        'valores' => ['Servicios', 'servicios'],
    ];

    public function historiaTitulo(): string
    {
        return filled($this->historia_titulo) ? $this->historia_titulo : self::HISTORIA_TITULO;
    }

    public function valoresTitulo(): string
    {
        return filled($this->valores_titulo) ? $this->valores_titulo : self::VALORES_TITULO;
    }
}
