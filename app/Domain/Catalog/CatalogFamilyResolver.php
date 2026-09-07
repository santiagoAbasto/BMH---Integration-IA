<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Domain\Catalog\Contracts\CatalogRepositoryInterface;
use App\Domain\Catalog\DTO\ProductView;

/**
 * Cómo se agrupan y cómo se nombran las piezas en el catálogo de BMH.
 *
 * Dos convenciones de la casa que hay que conocer para no equivocarse:
 *
 * 1. **El rubro se dice de muchas formas.** El cliente escribe "regulador", la
 *    IA devuelve "regulador de voltaje" y la base dice "REGULADOR DE VOLTAJE".
 *    Hay que elegir el rubro que MEJOR encaja, no todos los que comparten una
 *    palabra: "arranque" está en MOTORES DE ARRANQUE, en PORTAESCOBILLAS DE
 *    ARRANQUE y en ESCOBILLAS DE ARRANQUE Y ALTERNADOR.
 *
 * 2. **El código lleva la familia adelante.** En la pieza está grabado "17705";
 *    en el catálogo es REG17705 si es un regulador y POL17705 si es una polea.
 *    Sabiendo el rubro, un número suelto deja de ser una coincidencia parcial y
 *    pasa a ser el código exacto.
 *
 * Vive acá y no en el buscador porque no es una técnica de búsqueda: es
 * conocimiento sobre cómo BMH nombra sus cosas. Lo usan tanto el camino de
 * texto como el de foto.
 */
final class CatalogFamilyResolver
{
    /** Debajo de esto, el parecido entre dos nombres de rubro no es señal. */
    private const PARECIDO_MINIMO = 0.5;

    private const PALABRAS_VACIAS = ['de', 'del', 'la', 'el', 'los', 'las', 'y', 'para', 'con', 'a', 'un', 'una'];

    public function __construct(
        private readonly CatalogRepositoryInterface $catalog,
    ) {
    }

    /**
     * Los rubros que mejor encajan con lo que se dijo.
     *
     * Devuelve los que empatan en el mejor puntaje —normalmente uno— y nunca
     * todos los que comparten una palabra suelta.
     *
     * @param  list<string> $expresiones cómo se nombró la pieza
     * @return list<int>
     */
    public function rubros(array $expresiones): array
    {
        foreach ($expresiones as $expresion) {
            $mejorPuntaje = 0.0;
            $mejores      = [];

            foreach ($this->catalog->categories() as $categoria) {
                $puntaje = $this->parecido($expresion, $categoria->name);

                if ($puntaje < self::PARECIDO_MINIMO) {
                    continue;
                }

                if ($puntaje > $mejorPuntaje + 0.001) {
                    $mejorPuntaje = $puntaje;
                    $mejores      = [$categoria->id];
                } elseif (abs($puntaje - $mejorPuntaje) <= 0.001) {
                    $mejores[] = $categoria->id;
                }
            }

            if ($mejores !== []) {
                return array_values(array_unique($mejores));
            }
        }

        return [];
    }

    /**
     * Completa un número leído o tipeado con el prefijo de familia del rubro.
     *
     * Sólo aplica a códigos puramente numéricos: si ya trae letras, el código
     * está completo y no hay nada que reconstruir.
     *
     * @param  list<int> $rubros
     * @return list<ProductView>
     */
    public function completarCodigo(string $codigo, array $rubros): array
    {
        $numero = trim($codigo);

        if ($rubros === [] || preg_match('/^\d{3,}$/', $numero) !== 1) {
            return [];
        }

        foreach ($rubros as $rubro) {
            foreach ($this->catalog->codePrefixes($rubro) as $prefijo) {
                $encontrados = $this->catalog->findByCode($prefijo . $numero);

                if ($encontrados !== []) {
                    return $encontrados;
                }
            }
        }

        return [];
    }

    /**
     * Códigos del rubro que se parecen mucho al número leído.
     *
     * El OCR sobre números estampados en bajorrelieve se come dígitos: en una
     * pieza que dice 17705 leyó "1775". La diferencia es un carácter, y dentro
     * del rubro correcto no hay ambigüedad: es lo que preguntaría cualquiera en
     * el mostrador —"¿no será 17705?"— en vez de dar la consulta por perdida.
     *
     * Se busca sólo dentro de los rubros que la foto identificó y con una
     * distancia máxima chica: sin esas dos condiciones aparecerían parecidos de
     * cualquier familia y volveríamos al problema que esto viene a resolver.
     *
     * @param  list<int> $rubros
     * @return list<string> códigos completos del catálogo, los más parecidos primero
     */
    public function codigosParecidos(string $leido, array $rubros, int $distanciaMaxima = 1): array
    {
        $numero = preg_replace('/\D/', '', $leido) ?? '';

        if ($rubros === [] || mb_strlen($numero) < 4) {
            return [];
        }

        $parecidos = [];

        foreach ($rubros as $rubro) {
            foreach ($this->catalog->codesIn($rubro) as $codigo) {
                $suyo = preg_replace('/\D/', '', $codigo) ?? '';

                if ($suyo === '' || abs(mb_strlen($suyo) - mb_strlen($numero)) > $distanciaMaxima) {
                    continue;
                }

                $distancia = levenshtein($numero, $suyo);

                if ($distancia > 0 && $distancia <= $distanciaMaxima) {
                    $parecidos[$codigo] = $distancia;
                }
            }
        }

        asort($parecidos);

        return array_keys($parecidos);
    }

    /**
     * Si algún producto cae en el rubro esperado, se queda sólo con esos.
     *
     * El código 17705 existe como regulador y como polea. Si se pidió un
     * regulador, ofrecer la polea no es "una opción más": es ruido que hace
     * dudar del resto. Cuando ninguno cae en el rubro se devuelven todos,
     * porque entonces el equivocado puede ser el rubro.
     *
     * @param  list<ProductView> $productos
     * @param  list<int>         $rubros
     * @return list<ProductView>
     */
    public function preferirDelRubro(array $productos, array $rubros): array
    {
        if ($productos === [] || $rubros === []) {
            return $productos;
        }

        $delRubro = array_values(array_filter(
            $productos,
            static fn (ProductView $p): bool => in_array($p->category?->id, $rubros, true),
        ));

        return $delRubro === [] ? $productos : $delRubro;
    }

    /**
     * Qué tan parecidos son dos nombres de rubro, de 0 a 1.
     *
     * Se compara por palabras significativas, no por substring: así "motores de
     * arranque" no puntúa igual contra "MOTORES DE ARRANQUE" que contra
     * "PORTAESCOBILLAS DE ARRANQUE".
     */
    public function parecido(string $loQueSeDijo, string $nombreDelRubro): float
    {
        $a = $this->palabras($loQueSeDijo);
        $b = $this->palabras($nombreDelRubro);

        if ($a === [] || $b === []) {
            return 0.0;
        }

        if ($a === $b) {
            return 1.0;
        }

        $comunes = count(array_intersect($a, $b));

        if ($comunes === 0) {
            return 0.0;
        }

        /*
         * Se mide cuánto del RUBRO aparece en lo que se dijo, no al revés.
         *
         * Dividir por el texto más largo hacía que una frase entera diluyera el
         * rubro: "necesito el regulador 17705" tiene tres palabras y sólo una
         * coincide, pero nombra REGULADOR DE VOLTAJE tan claramente como decirlo
         * solo. Al revés, compartir una palabra con un rubro de tres —"arranque"
         * en ESCOBILLAS DE ARRANQUE Y ALTERNADOR— sigue puntuando bajo.
         */
        return $comunes / count($b);
    }

    /**
     * Palabras significativas, normalizadas y en singular.
     *
     * El singular importa: la IA dice "regulador de voltaje" y el rubro se llama
     * "REGULADOR DE VOLTAJE", pero también dice "motores de arranque" contra
     * "MOTORES DE ARRANQUE". Sin plegar el plural, uno de los dos casos falla.
     *
     * @return list<string>
     */
    public function palabras(string $texto): array
    {
        $partes = preg_split('/[^\p{L}\p{N}]+/u', $this->plegar($texto)) ?: [];

        $limpias = [];

        foreach ($partes as $parte) {
            if ($parte === '' || mb_strlen($parte) < 2 || in_array($parte, self::PALABRAS_VACIAS, true)) {
                continue;
            }

            $limpias[] = $this->singular($parte);
        }

        $limpias = array_values(array_unique($limpias));
        sort($limpias);

        return $limpias;
    }

    /** Plural castellano al singular, sin pretensiones de lingüista. */
    private function singular(string $palabra): string
    {
        if (mb_strlen($palabra) <= 3) {
            return $palabra;
        }

        foreach (['es', 's'] as $terminacion) {
            if (str_ends_with($palabra, $terminacion)) {
                $raiz = mb_substr($palabra, 0, -mb_strlen($terminacion));

                if (mb_strlen($raiz) >= 3) {
                    return $raiz;
                }
            }
        }

        return $palabra;
    }

    private function plegar(string $valor): string
    {
        return strtr(mb_strtolower(trim($valor)), [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
    }
}
