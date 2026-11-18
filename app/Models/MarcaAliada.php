<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Un logo de «Trabajamos con» en la Home (un cliente o un proveedor): el nombre (texto alternativo), el logo, un link
 * opcional a su sitio, el orden (dos caracteres, como el resto del admin) y si se muestra.
 *
 * `logo` es la ruta desde la raíz web: img/rediseno/… para los de fábrica, imagenes/… para los subidos.
 */
final class MarcaAliada extends Model
{
    protected $table = 'marcas_aliadas';

    protected $fillable = ['nombre', 'logo', 'url', 'orden', 'visible'];

    /** Carpeta (desde la raíz web) donde se guardan los logos que se suben desde el admin. */
    public const CARPETA = 'imagenes';

    private const ALFABETO_ORDEN = '0123456789abcdefghijklmnopqrstuvwxyz';

    protected function casts(): array
    {
        return ['visible' => 'boolean'];
    }

    protected static function booted(): void
    {
        self::saving(function (MarcaAliada $marca): void {
            $marca->orden = strtolower(trim((string) $marca->orden)) ?: self::siguienteOrden();
        });
    }

    /** @param  Builder<MarcaAliada>  $query */
    public function scopeOrdenadas(Builder $query): void
    {
        $query->orderBy('orden')->orderBy('id');
    }

    /** @param  Builder<MarcaAliada>  $query */
    public function scopeVisibles(Builder $query): void
    {
        $query->where('visible', true);
    }

    /** Con la fecha del archivo en la URL: si se reemplaza el archivo (mismo nombre), el navegador no usa el viejo. */
    public function logoUrl(): string
    {
        $archivo = public_path($this->logo);
        $version = is_file($archivo) ? '?v='.filemtime($archivo) : '';

        return asset($this->logo).$version;
    }

    /** Si el logo lo subió alguien desde el admin (y por lo tanto se puede borrar al reemplazarlo). */
    public function logoSubido(): bool
    {
        return str_starts_with((string) $this->logo, self::CARPETA.'/marca_');
    }

    /** El siguiente orden libre después del último, para que un logo nuevo quede al final. */
    public static function siguienteOrden(): string
    {
        $ultimo = self::query()->pluck('orden')
            ->filter(fn ($orden): bool => preg_match('/^[a-z0-9]{2}$/', (string) $orden) === 1)
            ->max();
        if ($ultimo === null) {
            return 'aa';
        }

        $alfabeto = self::ALFABETO_ORDEN;
        $base = strlen($alfabeto);
        $valor = strpos($alfabeto, $ultimo[0]) * $base + strpos($alfabeto, $ultimo[1]) + 1;

        return $valor >= $base * $base ? 'zz' : $alfabeto[intdiv($valor, $base)].$alfabeto[$valor % $base];
    }
}
