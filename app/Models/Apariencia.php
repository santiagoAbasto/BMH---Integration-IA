<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

/**
 * Colores editables del header y el footer del sitio (Extras en el admin).
 *
 * Una sola fila. El layout del front la lee en cada request, por eso se
 * cachea para siempre y se invalida al guardar.
 *
 * El header no es un solo juego de colores: son cinco estados que el visitante
 * ve en momentos distintos y que se editan por separado (ver SETS). Todo lo
 * demás —columnas, validación, variables CSS, campos del formulario— se deriva
 * de esa constante para que no haya dos listas que mantener en sincronía.
 */
class Apariencia extends Model
{
    protected $table = 'apariencia';

    /** Qué logo del header se usa en un estado dado. */
    public const LOGO_TRANSPARENTE = 'transparente';

    public const LOGO_BLANCO = 'blanco';

    public const LOGOS = [self::LOGO_TRANSPARENTE, self::LOGO_BLANCO];

    /**
     * Los tres selectores del editor. Cada uno muestra sus estados y su vista
     * previa; el visitante nunca ve dos modos a la vez.
     */
    public const MODOS = [
        'transparente' => 'Fondo transparente',
        'blanco' => 'Fondo blanco',
        'celular' => 'Celular',
    ];

    /** Colores del header propiamente dicho, en el orden en que se editan. */
    public const COLORES_HEADER = ['fondo', 'links', 'links_hover'];

    /** Colores del botón «Zona Clientes». */
    public const COLORES_BOTON = ['boton', 'boton_relleno', 'boton_hover_texto'];

    /** Nombre de cada color en el formulario. Un set puede pisarlos. */
    public const ETIQUETAS = [
        'fondo' => 'Fondo',
        'links' => 'Links',
        'links_hover' => 'Links al pasar el mouse',
        'boton' => 'Borde y texto',
        'boton_relleno' => 'Relleno al pasar el mouse',
        'boton_hover_texto' => 'Texto al pasar el mouse',
    ];

    /**
     * Los cinco estados del header, cada uno con su juego completo de colores.
     *
     * Los `defaults` reproducen exactamente lo que hoy está fijo en
     * public/css/styles2.css, así que instalar esto no cambia nada visible
     * hasta que alguien edite desde el admin.
     *
     * - modo:    con cuál de los tres selectores se muestra.
     * - prefijo: prefijo de las columnas y del `name` de los inputs.
     * - var:     prefijo de la variable CSS que lee el front.
     * - fondo:   false cuando el estado no puede tener color de fondo.
     */
    public const SETS = [
        'transparente_reposo' => [
            'modo' => 'transparente',
            'prefijo' => 'header_transparente_reposo',
            'var' => 'tr',
            'titulo' => 'Al cargar la página',
            'ayuda' => 'El header flota sobre la imagen de portada de la Home. No tiene color de fondo: se ve la foto detrás.',
            'fondo' => false,
            'defaults' => [
                'links' => '#FFFFFF',
                'links_hover' => '#0098DA',
                'boton' => '#FFFFFF',
                'boton_relleno' => '#0098DA',
                'boton_hover_texto' => '#FFFFFF',
                'logo' => self::LOGO_TRANSPARENTE,
            ],
        ],
        'transparente_scroll' => [
            'modo' => 'transparente',
            'prefijo' => 'header_transparente_scroll',
            'var' => 'ts',
            'titulo' => 'Al hacer scroll',
            'ayuda' => 'Cuando el visitante baja un poco la Home, el header queda fijo arriba y ya no se apoya sobre la foto.',
            'fondo' => true,
            'defaults' => [
                'fondo' => '#0098DA',
                'links' => '#FFFFFF',
                'links_hover' => '#FFFFFF',
                'boton' => '#FFFFFF',
                'boton_relleno' => '#0098DA',
                'boton_hover_texto' => '#FFFFFF',
                'logo' => self::LOGO_TRANSPARENTE,
            ],
        ],
        'blanco_reposo' => [
            'modo' => 'blanco',
            'prefijo' => 'header_blanco_reposo',
            'var' => 'br',
            'titulo' => 'Al cargar la página',
            'ayuda' => 'Nosotros, Productos, Novedades, Contacto, la búsqueda y la Zona de Clientes. Acá el fondo sí se puede cambiar: no tiene por qué quedar blanco.',
            'fondo' => true,
            'defaults' => [
                'fondo' => '#FFFFFF',
                'links' => '#000000',
                'links_hover' => '#0098DA',
                'boton' => '#0098DA',
                'boton_relleno' => '#0098DA',
                'boton_hover_texto' => '#FFFFFF',
                'logo' => self::LOGO_BLANCO,
            ],
        ],
        'blanco_scroll' => [
            'modo' => 'blanco',
            'prefijo' => 'header_blanco_scroll',
            'var' => 'bs',
            'titulo' => 'Al hacer scroll',
            'ayuda' => 'Las mismas páginas cuando el visitante baja y el header queda fijo arriba.',
            'fondo' => true,
            'defaults' => [
                'fondo' => '#0098DA',
                'links' => '#FFFFFF',
                'links_hover' => '#FFFFFF',
                'boton' => '#FFFFFF',
                'boton_relleno' => '#0098DA',
                'boton_hover_texto' => '#FFFFFF',
                'logo' => self::LOGO_TRANSPARENTE,
            ],
        ],
        'celular' => [
            'modo' => 'celular',
            'prefijo' => 'header_celular',
            'var' => 'cel',
            'titulo' => 'Header y menú',
            'ayuda' => 'En el celular el header es siempre el mismo: igual en todas las páginas y no cambia al hacer scroll. El fondo pinta también el menú desplegado.',
            'fondo' => true,
            'etiquetas' => [
                'links_hover' => 'Links al tocarlos',
                'boton_relleno' => 'Relleno al tocarlo',
                'boton_hover_texto' => 'Texto al tocarlo',
            ],
            'defaults' => [
                'fondo' => '#FFFFFF',
                'links' => '#000000',
                'links_hover' => '#000000',
                'boton' => '#0098DA',
                'boton_relleno' => '#0098DA',
                'boton_hover_texto' => '#FFFFFF',
                'logo' => self::LOGO_BLANCO,
            ],
        ],
    ];

    /** `derechos` es la franja de abajo del todo, con el copyright. */
    public const CAMPOS_FOOTER = [
        'footer_fondo', 'footer_texto', 'footer_texto_hover',
        'footer_derechos_fondo', 'footer_derechos_texto',
    ];

    public const DEFAULTS_FOOTER = [
        'footer_fondo' => '#0098DA',
        'footer_texto' => '#FFFFFF',
        'footer_texto_hover' => '#201E1E',
        'footer_derechos_fondo' => '#241F21',
        'footer_derechos_texto' => '#FFFFFF',
    ];

    private const CACHE_KEY = 'apariencia.actual';

    /**
     * Las columnas se generan a partir de SETS: enumerarlas acá sería una
     * segunda lista que se desincroniza. Sólo se llena con lo que ya pasó por
     * ActualizarHeaderRequest / ActualizarFooterRequest.
     */
    protected $guarded = ['id'];

    protected static function booted(): void
    {
        static::saved(fn () => self::olvidarCache());
    }

    /**
     * Descarta la fila cacheada. Además de al guardar, hace falta cuando cambia
     * el esquema: la caché guarda el modelo entero, con las columnas de antes.
     */
    public static function olvidarCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // --------------------------------------------------------------- esquema

    /**
     * Los colores que tiene un set, en el orden en que se editan.
     *
     * @return list<string>
     */
    public static function coloresDe(string $set): array
    {
        $header = self::SETS[$set]['fondo']
            ? self::COLORES_HEADER
            : array_values(array_diff(self::COLORES_HEADER, ['fondo']));

        return [...$header, ...self::COLORES_BOTON];
    }

    /** Nombre de la columna de un color (o de `logo`) dentro de un set. */
    public static function campo(string $set, string $color): string
    {
        return self::SETS[$set]['prefijo'].'_'.$color;
    }

    /**
     * Todas las columnas de color del header.
     *
     * @return list<string>
     */
    public static function camposHeader(): array
    {
        $campos = [];

        foreach (array_keys(self::SETS) as $set) {
            foreach (self::coloresDe($set) as $color) {
                $campos[] = self::campo($set, $color);
            }
        }

        return $campos;
    }

    /**
     * Las columnas que eligen qué logo muestra cada estado.
     *
     * @return list<string>
     */
    public static function camposLogo(): array
    {
        return array_map(fn (string $set) => self::campo($set, 'logo'), array_keys(self::SETS));
    }

    /**
     * Valores de fábrica de toda la tabla. La migración los usa como default
     * de cada columna, así que las dos listas salen de acá.
     *
     * @return array<string, string>
     */
    public static function defaults(): array
    {
        // El layout del front lo consulta una vez por color y por request.
        static $valores = null;

        if ($valores === null) {
            $valores = [];

            foreach (self::SETS as $set => $config) {
                foreach ([...self::coloresDe($set), 'logo'] as $color) {
                    $valores[self::campo($set, $color)] = $config['defaults'][$color];
                }
            }

            $valores = [...$valores, ...self::DEFAULTS_FOOTER];
        }

        return $valores;
    }

    /** La etiqueta de un color, con la variante que haya pedido el set. */
    public static function etiqueta(string $set, string $color): string
    {
        return self::SETS[$set]['etiquetas'][$color] ?? self::ETIQUETAS[$color];
    }

    /** Cómo se llama un color dentro de una variable CSS: `btn-relleno`. */
    public static function sufijoCss(string $color): string
    {
        return str_replace(['boton', '_'], ['btn', '-'], $color);
    }

    /** Nombre de la variable CSS de un color, p. ej. `--ap-bs-btn-relleno`. */
    public static function variable(string $set, string $color): string
    {
        return '--ap-'.self::SETS[$set]['var'].'-'.self::sufijoCss($color);
    }

    /**
     * Qué parte de la vista previa del admin resalta un color.
     *
     * Varios colores apuntan a la misma parte: los dos de links marcan los
     * links, y los tres del botón marcan el botón.
     */
    public static function parteDe(string $set, string $color): string
    {
        $parte = match (true) {
            $color === 'fondo' => 'fondo',
            str_starts_with($color, 'links') => 'links',
            $color === 'logo' => 'logo',
            default => 'btn',
        };

        return self::SETS[$set]['var'].'-'.$parte;
    }

    /**
     * Contra qué otro color se mide el contraste, para avisar cuando un texto
     * o un borde va a costar de leer. Null cuando no hay con qué comparar.
     */
    public static function contrasteDe(string $set, string $color): ?string
    {
        $contra = match ($color) {
            'links', 'links_hover', 'boton' => 'fondo',
            'boton_hover_texto' => 'boton_relleno',
            default => null,
        };

        if ($contra === null || ! in_array($contra, self::coloresDe($set), true)) {
            return null;
        }

        return self::campo($set, $contra);
    }

    /**
     * Qué variable CSS le corresponde a cada columna de color, para que el
     * editor del admin pinte la vista previa sin repetir el mapeo en JS.
     *
     * @return array<string, string>
     */
    public static function mapaVariables(): array
    {
        $mapa = [];

        foreach (array_keys(self::SETS) as $set) {
            foreach (self::coloresDe($set) as $color) {
                $mapa[self::campo($set, $color)] = self::variable($set, $color);
            }
        }

        foreach (self::CAMPOS_FOOTER as $campo) {
            $mapa[$campo] = self::variableFooter($campo);
        }

        return $mapa;
    }

    private static function variableFooter(string $campo): string
    {
        return '--ap-f-'.str_replace(['footer_', '_'], ['', '-'], $campo);
    }

    // --------------------------------------------------------------- lectura

    /**
     * La fila vigente. Si la tabla todavía no existe —se subieron los archivos
     * pero falta correr la migración— devuelve los valores de fábrica en vez
     * de tirar abajo el sitio entero.
     */
    public static function actual(): self
    {
        try {
            return Cache::rememberForever(self::CACHE_KEY, function () {
                return static::query()->first() ?? static::query()->create(self::defaults());
            });
        } catch (QueryException) {
            return new static(self::defaults());
        }
    }

    /** El valor guardado de un campo, o el de fábrica si quedó vacío. */
    public function valor(string $campo): string
    {
        return $this->{$campo} ?: self::defaults()[$campo];
    }

    /** Qué logo muestra un estado: `transparente` o `blanco`. */
    public function logoDe(string $set): string
    {
        $elegido = $this->{self::campo($set, 'logo')};

        return in_array($elegido, self::LOGOS, true) ? $elegido : self::SETS[$set]['defaults']['logo'];
    }

    /**
     * Variables CSS que consume el layout del front.
     *
     * @return array<string, string>
     */
    public function variablesCss(): array
    {
        $variables = [];

        foreach (self::mapaVariables() as $campo => $variable) {
            $variables[$variable] = $this->valor($campo);
        }

        return $variables;
    }
}
