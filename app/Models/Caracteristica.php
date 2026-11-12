<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Caracteristicas\FormatoCaracteristica;
use App\Support\Caracteristicas\MigracionCaracteristicas;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * Una característica de producto (VOLTAJE, DIÁMETRO, CÓDIGO ZEN…).
 *
 *  - Las categorías declaran cuáles usan sus productos (`caracteristica_categoria`): es la ficha que se
 *    muestra en el sitio y por la que se filtra el catálogo.
 *  - Cada producto guarda su valor (`caracteristica_producto.valor`).
 *  - `orden`: 2 caracteres (aa, ab, …), como el resto del sitio; manda el orden en la ficha y en los filtros.
 *  - Formato opcional (FormatoCaracteristica): en mayúsculas, numérica y con unidad (VOLTAJE → «12V»).
 */
class Caracteristica extends Model
{
    use HasFactory;

    protected $table = 'caracteristicas';

    protected $fillable = [
        'orden',
        'nombre',
        'mayusculas',
        'numerica',
        'unidad',
    ];

    protected $attributes = [
        'mayusculas' => true,
        'numerica' => false,
    ];

    protected $casts = [
        'mayusculas' => 'boolean',
        'numerica' => 'boolean',
    ];

    /** Alfabeto del orden: 0-9 y a-z, en el mismo orden en que los compara la base. */
    private const ALFABETO_ORDEN = '0123456789abcdefghijklmnopqrstuvwxyz';

    public function categorias(): BelongsToMany
    {
        return $this->belongsToMany(Categoria::class, 'caracteristica_categoria')->withTimestamps();
    }

    public function productos(): BelongsToMany
    {
        return $this->belongsToMany(Producto::class, 'caracteristica_producto')
            ->withPivot('valor')
            ->withTimestamps();
    }

    public function formato(): FormatoCaracteristica
    {
        return new FormatoCaracteristica(
            (bool) $this->mayusculas,
            (bool) $this->numerica,
            $this->unidad !== null && trim((string) $this->unidad) !== '' ? (string) $this->unidad : null,
        );
    }

    /** Cómo se guarda, en palabras: «numérica · se guarda con V», «texto en mayúsculas». */
    public function descripcionFormato(): string
    {
        return $this->formato()->descripcion();
    }

    /** Lo que necesitan los formularios del admin (y su JS) para mostrar y validar una característica. */
    public function paraFormulario(): array
    {
        return [
            'id' => $this->id,
            'nombre' => (string) $this->nombre,
            'mayusculas' => (bool) $this->mayusculas,
            'numerica' => (bool) $this->numerica,
            'unidad' => $this->unidad !== null && $this->unidad !== '' ? (string) $this->unidad : null,
            'formato' => $this->descripcionFormato(),
        ];
    }

    /** Por `orden` (las que no tienen van al final) y después por nombre. */
    public function scopeOrdenadas(Builder $query): Builder
    {
        return $query
            ->orderByRaw("COALESCE(caracteristicas.orden, '') = ''")
            ->orderBy('caracteristicas.orden')
            ->orderBy('caracteristicas.nombre')
            ->orderBy('caracteristicas.id');
    }

    /** Las que no usa ninguna categoría ni ningún producto. */
    public function scopeSinUso(Builder $query): Builder
    {
        return $query->whereDoesntHave('categorias')->whereDoesntHave('productos');
    }

    /**
     * Filtro del buscador del admin. Sin término devuelve la query intacta.
     */
    public function scopeBuscar(Builder $query, ?string $termino): Builder
    {
        $termino = trim((string) $termino);

        if ($termino === '') {
            return $query;
        }

        // Se escapan los comodines para que un '%' tipeado no traiga todo.
        $patron = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $termino).'%';

        return $query->where('caracteristicas.nombre', 'like', $patron);
    }

    /** Criterios de orden del listado del admin (query string `ordenar`). */
    public const ORDEN_CAMPO = 'orden';

    public const ORDEN_AZ = 'az';

    public const ORDEN_ZA = 'za';

    public const ORDEN_USO = 'uso';

    public const CRITERIOS_DE_ORDEN = [self::ORDEN_CAMPO, self::ORDEN_AZ, self::ORDEN_ZA, self::ORDEN_USO];

    /**
     * Orden del listado del admin. En todos los criterios se desempata por nombre e id para que la
     * paginación sea estable. «uso» necesita los conteos (withCount de productos y categorías).
     */
    public function scopeOrdenadoPor(Builder $query, string $criterio): Builder
    {
        return match ($criterio) {
            self::ORDEN_AZ => $query->orderBy('caracteristicas.nombre')->orderBy('caracteristicas.id'),
            self::ORDEN_ZA => $query->orderByDesc('caracteristicas.nombre')->orderByDesc('caracteristicas.id'),
            self::ORDEN_USO => $query->orderByDesc('productos_count')->orderByDesc('categorias_count')
                ->orderBy('caracteristicas.nombre')->orderBy('caracteristicas.id'),
            default => $query->ordenadas(),
        };
    }

    /**
     * ¿Existe otra característica con este nombre? Compara sin tildes, mayúsculas ni signos (como la
     * migración): «Diámetro» y «DIAMETRO» son la misma. Excluye la propia al editar.
     */
    public static function nombreOcupado(string $nombre, ?int $exceptoId = null): ?self
    {
        $clave = MigracionCaracteristicas::clave($nombre);

        return static::query()
            ->when($exceptoId !== null, fn (Builder $q) => $q->whereKeyNot($exceptoId))
            ->get(['id', 'nombre'])
            ->first(fn (self $c) => MigracionCaracteristicas::clave($c->nombre) === $clave);
    }

    /** El nombre como se guarda: sin espacios de más y en mayúsculas, como todos los que ya había. */
    public static function normalizarNombre(?string $nombre): string
    {
        return mb_strtoupper(trim((string) preg_replace('/\s+/u', ' ', (string) $nombre)), 'UTF-8');
    }

    /** El orden como se guarda: 2 caracteres en minúscula, o null. */
    public static function normalizarOrden(?string $orden): ?string
    {
        $orden = strtolower(trim((string) $orden));

        return preg_match('/^[a-z0-9]{2}$/', $orden) === 1 ? $orden : null;
    }

    /** El siguiente orden libre después del último (para que una nueva quede al final). */
    public static function siguienteOrden(): string
    {
        $ultimo = static::query()
            ->whereRaw("orden REGEXP '^[a-z0-9]{2}$'")
            ->max('orden');

        if ($ultimo === null) {
            return 'aa';
        }

        $alfabeto = self::ALFABETO_ORDEN;
        $base = strlen($alfabeto);
        $valor = strpos($alfabeto, $ultimo[0]) * $base + strpos($alfabeto, $ultimo[1]) + 1;
        if ($valor >= $base * $base) {
            return 'zz';
        }

        return $alfabeto[intdiv($valor, $base)].$alfabeto[$valor % $base];
    }
}
