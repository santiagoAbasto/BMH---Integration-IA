<?php

declare(strict_types=1);

namespace App\Domain\Search;

use App\Domain\Search\DTO\SearchQuery;

/**
 * Decide qué estrategia de búsqueda corre.
 *
 * No todo necesita IA. Si el cliente escribe "REG40016", eso es una búsqueda
 * determinística por código: gastar una llamada a Gemini para entenderlo sería
 * tirar plata y latencia.
 *
 * @see docs/catalog-search.md §"QueryRouter"
 */
final class QueryRouter
{
    public const EXACT      = 'exact';
    public const STRUCTURED = 'structured';
    public const SEMANTIC   = 'semantic';
    public const VISION     = 'vision';
    public const HYBRID     = 'hybrid';
    public const HISTORY    = 'history';

    /**
     * Códigos de BMH: 3+ caracteres, mayúsculas y dígitos, con al menos un
     * dígito. Cubre "REG40016", "RP033155", "1833".
     */
    private const CODE_PATTERN = '/^[A-Z0-9][A-Z0-9\s\-\.\/]{2,}$/i';

    public function route(SearchQuery $query, bool $hasImage = false): string
    {
        if ($hasImage) {
            // Con imagen siempre es híbrido: la visión aporta atributos, pero
            // la búsqueda estructurada es la que manda.
            return self::HYBRID;
        }

        if ($query->hasCode()) {
            return self::EXACT;
        }

        if ($query->hasText() && $this->looksLikeCode((string) $query->rawText)) {
            return self::EXACT;
        }

        if ($query->hasStructuredFilters()) {
            return $query->hasText() ? self::HYBRID : self::STRUCTURED;
        }

        if ($query->customerProductIds !== [] && ! $query->hasText()) {
            return self::HISTORY;
        }

        return $query->hasText() ? self::SEMANTIC : self::STRUCTURED;
    }

    /**
     * ¿Este texto suelto es en realidad un código?
     *
     * Se pide que no tenga espacios internos significativos ni parezca una
     * frase: "912345" sí, "necesito un rotor" no.
     */
    public function looksLikeCode(string $text): bool
    {
        $text = trim($text);

        if ($text === '' || mb_strlen($text) > 24) {
            return false;
        }

        // Más de dos palabras ya es una frase, no un código.
        if (count(preg_split('/\s+/', $text) ?: []) > 2) {
            return false;
        }

        if (! preg_match(self::CODE_PATTERN, $text)) {
            return false;
        }

        // Tiene que tener al menos un dígito: "rotor" matchea el patrón pero no
        // es un código.
        return (bool) preg_match('/\d/', $text);
    }

    /**
     * Extrae un código embebido en una frase: "tenés el 912345?"
     *
     * Los códigos de BMH van pegados (REG40016, RP033155, IMPO1629, PLA18456)
     * o con guiones (CY-EV2-22KW). Por eso NO se admite espacio entre la parte
     * alfabética y la numérica: si se admitiera, "el 912345" daría "el 912345",
     * con el artículo adentro.
     */
    public function extractCode(string $text): ?string
    {
        // Artículos y preposiciones que preceden a un número en el habla normal.
        static $stopPrefixes = ['el', 'la', 'un', 'una', 'los', 'las', 'de', 'del', 'al', 'en', 'con', 'y', 'o'];

        if (preg_match('/\b([A-Za-z]{1,6}-?\d{2,8}(?:-[A-Za-z0-9]{1,6})*)\b/', $text, $m)) {
            $candidate = trim($m[1]);

            preg_match('/^[A-Za-z]+/', $candidate, $prefix);

            if (! in_array(mb_strtolower($prefix[0] ?? ''), $stopPrefixes, true)) {
                return $candidate;
            }
        }

        // Números sueltos de 4+ dígitos. Con menos, es probablemente una medida.
        if (preg_match('/\b(\d{4,10})\b/', $text, $m)) {
            return $m[1];
        }

        return null;
    }
}
