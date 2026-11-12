<?php

declare(strict_types=1);

namespace App\Support;

use DOMDocument;

/**
 * Mapa de Contacto: lee el iframe de Google Maps u OpenStreetMap que se carga en el admin (copiado de
 * Conelec, que lo trajo de Rapivac). Con coordenadas en la URL, el sitio dibuja el mapa con Leaflet y el
 * pin azul de BMH (components/contact-map); sin coordenadas, queda el iframe en gris con el pin fijo.
 */
final class MapaEmbed
{
    /**
     * Los embeds traen su propio marcador, que no se puede ocultar dentro del iframe. En Google ("Compartir →
     * Insertar un mapa"), si la URL tiene el centro del mapa (!1d distancia, !2d longitud, !3d latitud), se arma
     * la misma vista sin marcador; en OpenStreetMap se quita "marker". El pin de BMH queda solo, en el centro.
     */
    public static function withoutMarker(string $url): string
    {
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $query);

        // OpenStreetMap: el marcador es el parámetro "marker" y el mapa queda centrado en su bbox.
        if (($parts['path'] ?? '') === '/export/embed.html') {
            unset($query['marker']);

            return 'https://'.$parts['host'].'/export/embed.html?'.http_build_query($query);
        }

        if (($parts['path'] ?? '') !== '/maps/embed') {
            return $url;
        }
        if (! is_string($query['pb'] ?? null) || ! preg_match('/!1m3!1d([0-9.]+)!2d(-?[0-9.]+)!3d(-?[0-9.]+)/', $query['pb'], $center)) {
            return $url;
        }

        return 'https://'.$parts['host'].'/maps/embed?pb=!1m14!1m12!1m3!1d'.$center[1].'!2d'.$center[2].'!3d'.$center[3]
            .'!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1ses-419!2sar!4v1700000000000!5m2!1ses-419!2sar';
    }

    /**
     * Ubicación del embed para dibujar el mapa con el marcador anclado: en OpenStreetMap, el "marker" (o el centro del
     * bbox) y el bbox como encuadre; en Google, el centro (!3d latitud, !2d longitud) y el zoom aproximado según la
     * distancia (!1d), o las coordenadas de "q" / "ll" con el zoom de "z" (google.com/maps?q=-34.66,-58.38&z=16).
     * Null si la URL no trae coordenadas (por ejemplo, una búsqueda por dirección): se usa el iframe.
     *
     * @return array{lat: float, lng: float, zoom: int, bounds: list<list<float>>|null}|null
     */
    public static function location(string $url): ?array
    {
        $parts = parse_url($url);
        parse_str($parts['query'] ?? '', $query);
        $number = '-?\d{1,3}(?:\.\d+)?';

        if (($parts['path'] ?? '') === '/export/embed.html' && is_string($query['bbox'] ?? null)
            && preg_match("/\\A($number),($number),($number),($number)\\z/", $query['bbox'], $box)) {
            [$west, $south, $east, $north] = array_map('floatval', array_slice($box, 1));
            $marker = is_string($query['marker'] ?? null) && preg_match("/\\A($number),($number)\\z/", $query['marker'], $point)
                ? [(float) $point[1], (float) $point[2]] : [($south + $north) / 2, ($west + $east) / 2];

            return self::valid(['lat' => $marker[0], 'lng' => $marker[1], 'zoom' => 16, 'bounds' => [[$south, $west], [$north, $east]]]);
        }

        if (($parts['path'] ?? '') === '/maps/embed' && is_string($query['pb'] ?? null)
            && preg_match("/!1d([0-9.]+)!2d($number)!3d($number)/", $query['pb'], $center)) {
            $zoom = (int) round(log(156543.03392 * 1024 / max((float) $center[1], 1), 2));

            return self::valid(['lat' => (float) $center[3], 'lng' => (float) $center[2], 'zoom' => max(3, min(19, $zoom)), 'bounds' => null]);
        }

        if (($parts['path'] ?? '') === '/maps') {
            foreach (['q', 'll'] as $key) {
                if (is_string($query[$key] ?? null) && preg_match("/\\A\\s*($number)\\s*,\\s*($number)\\s*\\z/", $query[$key], $point)) {
                    $zoom = is_numeric($query['z'] ?? null) ? (int) $query['z'] : 16;

                    return self::valid(['lat' => (float) $point[1], 'lng' => (float) $point[2], 'zoom' => max(3, min(19, $zoom)), 'bounds' => null]);
                }
            }
        }

        return null;
    }

    /** @param array{lat: float, lng: float, zoom: int, bounds: list<list<float>>|null} $location */
    private static function valid(array $location): ?array
    {
        $location['lat'] = round($location['lat'], 7);
        $location['lng'] = round($location['lng'], 7);

        return abs($location['lat']) <= 90 && abs($location['lng']) <= 180 ? $location : null;
    }

    /**
     * Link para abrir la ubicación en Google Maps (app en el celular, web en la computadora). Con la dirección,
     * Google muestra la ficha del lugar; sin ella, las coordenadas del mapa.
     *
     * @param  array{lat: float, lng: float}|null  $location
     */
    public static function googleMapsUrl(?string $address, ?array $location = null): ?string
    {
        $query = trim((string) preg_replace('/\s*\n\s*/', ', ', str_replace('\n', "\n", (string) $address)));

        if ($query === '' && $location !== null) {
            $query = $location['lat'].','.$location['lng'];
        }

        return $query === '' ? null : 'https://www.google.com/maps/search/?api=1&query='.rawurlencode($query);
    }

    /** Extrae solo la URL permitida: nunca se guarda ni se muestra el HTML recibido. */
    public static function url(string $input): ?string
    {
        $url = trim($input);
        if (str_contains($url, '<')) {
            $document = new DOMDocument;
            $previous = libxml_use_internal_errors(true);
            try {
                $document->loadHTML($url, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
                $frames = $document->getElementsByTagName('iframe');
                if ($frames->length !== 1 || $frames->item(0)->hasAttribute('srcdoc')) {
                    return null;
                }
                $url = trim($frames->item(0)->getAttribute('src'));
            } finally {
                libxml_clear_errors();
                libxml_use_internal_errors($previous);
            }
        }
        if (! filter_var($url, FILTER_VALIDATE_URL) || preg_match('/[\\\\\x00-\x20\x7f]/', $url)) {
            return null;
        }
        $parts = parse_url($url);
        if (($parts['scheme'] ?? '') !== 'https' || isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && $parts['port'] !== 443)) {
            return null;
        }
        $host = strtolower($parts['host'] ?? '');
        $path = $parts['path'] ?? '';
        $google = in_array($host, ['www.google.com', 'www.google.com.ar', 'maps.google.com', 'maps.google.com.ar'], true);
        $osm = in_array($host, ['www.openstreetmap.org', 'openstreetmap.org'], true) && $path === '/export/embed.html';
        if ($osm || ($google && in_array($path, ['/maps/embed', '/maps/embed/v1/place', '/maps/d/embed'], true))) {
            return $url;
        }
        if ($google && $path === '/maps') {
            parse_str($parts['query'] ?? '', $query);
            if (($query['output'] ?? null) === 'embed') {
                return $url;
            }
        }

        return null;
    }
}
