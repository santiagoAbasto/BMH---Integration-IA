<?php

declare(strict_types=1);

namespace App\Support\Seo;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/** Ayudas de texto para metadatos: texto plano, cortes por palabra y palabras clave sin repetir. */
final class SeoText
{
    public const TITLE_LIMIT = 60;

    public const DESCRIPTION_LIMIT = 160;

    public const KEYWORDS_LIMIT = 255;

    public static function plain(?string $html): string
    {
        $text = html_entity_decode(strip_tags(str_replace(['<br>', '</p>', '</li>', '</h2>', '</h3>'], ' ', $html ?? '')), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    /** Corta en la última palabra completa que entra, con "…" si hizo falta cortar. */
    public static function limit(string $text, int $limit): string
    {
        $text = trim($text);
        if (mb_strlen($text) <= $limit) {
            return $text;
        }
        $cut = mb_substr($text, 0, $limit - 1);
        $space = mb_strrpos($cut, ' ');

        return rtrim($space > $limit * 0.6 ? mb_substr($cut, 0, $space) : $cut, ' ,.;:-–·|').'…';
    }

    /** "Nombre | BMH" si entra en 60 caracteres; si no, solo el nombre. */
    public static function title(string $name, ?string $context = null): string
    {
        foreach (array_filter([$context ? "{$name} · {$context} | BMH" : null, "{$name} | BMH"]) as $candidate) {
            if (mb_strlen($candidate) <= self::TITLE_LIMIT) {
                return $candidate;
            }
        }

        return self::limit($name, self::TITLE_LIMIT);
    }

    /**
     * Palabras clave únicas (sin distinguir mayúsculas ni puntos finales), cortas y dentro de 255 caracteres.
     *
     * @param  iterable<?string>  $candidates
     * @return list<string>
     */
    public static function keywords(iterable $candidates, int $max = 15): array
    {
        $keywords = [];
        $length = 0;
        foreach ($candidates as $candidate) {
            $keyword = rtrim(self::plain($candidate), ' .;:,');
            $key = Str::lower($keyword);
            if ($keyword === '' || mb_strlen($keyword) > 60 || isset($keywords[$key])) {
                continue;
            }
            if ($length + mb_strlen($keyword) + 2 > self::KEYWORDS_LIMIT || count($keywords) >= $max) {
                break;
            }
            $keywords[$key] = $keyword;
            $length += mb_strlen($keyword) + 2;
        }

        return array_values($keywords);
    }

    /** @return list<string> */
    public static function split(?string $keywords): array
    {
        return self::keywords(Collection::make(explode(',', $keywords ?? '')));
    }
}
