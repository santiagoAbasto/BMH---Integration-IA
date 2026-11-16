<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Apariciones al hacer scroll en el sitio público (como en Conelec y Rapivac). Es la única lista de lo que se anima:
 * de acá salen el estado oculto inicial (en el <head>, antes de pintar, así no parpadea: layouts/partials/motion-head)
 * y lo que observa resources/js/components/motion.js. Para animar un bloque nuevo alcanza con sumar su selector a la
 * lista que corresponda. Las transiciones y la entrada del hero de la home están en resources/css/sitio/motion.css.
 *
 * El header no está en ninguna lista: tiene su propio comportamiento al scrollear. Las migas tampoco: son navegación.
 */
final class Motion
{
    /** Bloques de texto y formularios: suben con un fundido. */
    public const BLOCKS = [
        '.osole-info-split-v1__content',
        '.bmh-seccion-encabezado',
        '.bmh-nosotros-valores .bmh-seccion-titulo',
        '.bmh-filtro',
        '.bmh-vacio',
        '.bmh-contacto__form',
        '.bmh-zona__vacio',
        '.catalogo__cabecera',
        '.catalogo__vacio',
        '.novedad .texto',
    ];

    /** Fotos y mapas: se abren desde un recorte apenas más chico mientras se asientan. */
    public const MEDIA = [
        '.osole-info-split-v1__media',
        '.bmh-contacto-mapa__marco',
        '.novedadIN',
    ];

    /** Grillas de tarjetas: cada hijo sube cuando llega a la pantalla, en cascada con los que entran junto a él. */
    public const GRIDS = [
        '.bmh-grilla-rubros',
        '.bmh-grilla-novedades',
        '.bmh-grilla-valores',
        '.osole-footer-v1__columns',
    ];

    /**
     * Listas que se leen de corrido (datos de contacto, tablas, resultados del catálogo): lo mismo que las grillas,
     * pero con menos recorrido y una cascada más corta, para que no demoren la lectura.
     */
    public const ROWS = [
        '.bmh-contacto__datos',
        '.bmh-docs tbody',
        '.catalogo__lista',
    ];

    /**
     * Contenedores que el JS de la página rellena de nuevo por una acción de la persona (el catálogo al filtrar): lo
     * que queda a la vista aparece de una, sin cascada; lo que está más abajo aparece al scrollear, como siempre.
     */
    public const SWAPS = [
        '[data-catalogo-contenido]',
    ];

    /** @return array{blocks: list<string>, media: list<string>, grids: list<string>, rows: list<string>, swaps: list<string>} */
    public static function targets(): array
    {
        return [
            'blocks' => self::BLOCKS,
            'media' => self::MEDIA,
            'grids' => self::GRIDS,
            'rows' => self::ROWS,
            'swaps' => self::SWAPS,
        ];
    }

    /** Estado oculto inicial. Solo con html.motion (la pone motion-head si no se pidió reducir el movimiento). */
    public static function hiddenCss(): string
    {
        $children = fn (array $selectors): array => array_map(fn (string $selector): string => $selector.' > *', $selectors);
        $rising = implode(', ', [...self::BLOCKS, ...$children(self::GRIDS)]);
        $rows = implode(', ', $children(self::ROWS));
        $media = implode(', ', self::MEDIA);

        return "@media screen {\n"
            ."html.motion :is({$rising}):not(.is-revealed) { opacity: 0; translate: 0 1.5rem; }\n"
            ."html.motion :is({$rows}):not(.is-revealed) { opacity: 0; translate: 0 0.625rem; }\n"
            ."html.motion :is({$media}):not(.is-revealed) { opacity: 0; clip-path: inset(5% 4% round var(--motion-radius, 0px)); scale: 1.04; }\n"
            .'}';
    }
}
