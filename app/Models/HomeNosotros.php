<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\TextoAdmin;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

/**
 * El bloque «Nosotros» de la Home (dato único): imagen, título, descripción (texto enriquecido) y el botón (texto y
 * link). Se edita en admin → Home → Sección Nosotros.
 */
final class HomeNosotros extends Model
{
    protected $table = 'home_nosotros';

    protected $fillable = ['imagen', 'titulo', 'descripcion', 'boton_texto', 'boton_url'];

    public const CARPETA = 'imagenes';

    public const IMAGEN_DE_FABRICA = 'img/rediseno/planta-bmh.webp';

    public const TITULO_DE_FABRICA = 'Más de 55 años fabricando calidad para el automotor';

    /** La fila única (si alguien la borró, se usa una vacía con el título de fábrica). */
    public static function actual(): self
    {
        return self::query()->first() ?? new self(['titulo' => self::TITULO_DE_FABRICA, 'boton_texto' => 'Conocé más', 'boton_url' => '/nosotros']);
    }

    public function imagenUrl(): string
    {
        return asset($this->imagen ?: self::IMAGEN_DE_FABRICA);
    }

    public function imagenSubida(): bool
    {
        return str_starts_with((string) $this->imagen, self::CARPETA.'/home-nosotros_');
    }

    /** La descripción lista para mostrar: sin estilos pegados ni scripts; null si está vacía. */
    public function descripcionHtml(): ?HtmlString
    {
        $limpio = preg_replace(['#<(script|style|iframe|object|embed)\b.*?</\1>#is', '/\son[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '/(href|src)\s*=\s*"\s*javascript:[^"]*"/i'], ['', '', '$1="#"'], (string) $this->descripcion);

        return trim(strip_tags((string) $limpio)) === '' ? null : TextoAdmin::html($limpio);
    }

    /** El link del botón: una ruta del sitio (/nosotros) o una dirección completa. Null si no hay botón. */
    public function botonHref(): ?string
    {
        $url = trim((string) $this->boton_url);
        if ($url === '' || trim((string) $this->boton_texto) === '') {
            return null;
        }

        return str_starts_with($url, '/') ? url($url) : $url;
    }
}
