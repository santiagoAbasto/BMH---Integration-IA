<?php

declare(strict_types=1);

namespace App\Services\Ai\Providers;

use App\Domain\Catalog\LegacyAttributeMap;
use App\Services\Ai\Contracts\AiProviderInterface;
use App\Services\Ai\DTO\AiMessage;
use App\Services\Ai\DTO\AiResponse;
use App\Services\Ai\DTO\AiUsage;
use App\Services\Ai\DTO\ImageAnalysis;

/**
 * Proveedor simulado. Hace andar la demo completa sin API key.
 *
 * No es un stub que devuelve "lorem ipsum": interpreta el texto del cliente con
 * heurísticas reales (marca, voltaje, medidas, rubro, códigos) y produce la
 * misma estructura que devolvería Gemini u OpenAI.
 *
 * Que la demo se sienta inteligente con este provider no es casualidad: la
 * inteligencia del sistema vive en el orquestador, la búsqueda y la
 * desambiguación, no en el modelo. El modelo interpreta y redacta. Cambiar a
 * Gemini mejora la interpretación de lenguaje libre y habilita visión real,
 * pero no cambia qué producto se elige ni a qué precio.
 *
 * @see docs/ai-architecture.md §"MockAiProvider"
 */
final class MockAiProvider implements AiProviderInterface
{
    /** Marcas presentes en el catálogo de BMH. */
    private const BRANDS = [
        'BOSCH', 'VALEO', 'WAPSA', 'ARGELITE', 'DENSO', 'DELCO', 'MITSUBISHI',
        'HITACHI', 'MAGNETI', 'MARELLI', 'LUCAS', 'ISKRA', 'PRESTOLITE', 'ZEN',
        'CRAMACO', 'NIPPONDENSO', 'FEMSA', 'PAL',
    ];

    /** Palabra del cliente → rubro del catálogo. */
    private const PART_KEYWORDS = [
        'rotor'            => 'ROTORES',
        'inducido'         => 'INDUCIDOS',
        'estator'          => 'ESTATORES',
        'solenoide'        => 'SOLENOIDES',
        'automatico'       => 'SOLENOIDES',
        'impulsor'         => 'IMPULSORES',
        'bendix'           => 'IMPULSORES',
        'colector'         => 'COLECTORES DE ALTERNADOR',
        'plaqueta'         => 'PLAQUETA RECTIFICADORA',
        'rectificadora'    => 'PLAQUETA RECTIFICADORA',
        'puente'           => 'PLAQUETA RECTIFICADORA',
        'regulador'        => 'REGULADOR DE VOLTAJE',
        'campo'            => 'CAMPOS',
        'bobina'           => 'CAMPOS',
        'carcasa'          => 'CARCASAS',
        'portaescobilla'   => 'PORTAESCOBILLAS DE ARRANQUE',
        'porta escobilla'  => 'PORTAESCOBILLAS DE ARRANQUE',
        'escobilla'        => 'ESCOBILLAS DE ARRANQUE Y ALTERNADOR',
        'carbon'           => 'ESCOBILLAS DE ARRANQUE Y ALTERNADOR',
        'arranque'         => 'MOTORES DE ARRANQUE',
        'burro'            => 'MOTORES DE ARRANQUE',
        'alternador'       => 'ALTERNADORES',
        'rodamiento'       => 'RODAMIENTOS',
        'ruleman'          => 'RODAMIENTOS',
        'polea'            => 'POLEA CRIQUE Y POLI-V',
        'limpiaparabrisas' => 'MOTORES LIMPIAPARABRISAS - CALEFACTORES - SOPLADORES',
        'soplador'         => 'MOTORES LIMPIAPARABRISAS - CALEFACTORES - SOPLADORES',
        'calefactor'       => 'MOTORES LIMPIAPARABRISAS - CALEFACTORES - SOPLADORES',
        'ficha'            => 'FICHAS- EMULADORES- FUSIBLES',
        'fusible'          => 'FICHAS- EMULADORES- FUSIBLES',
        'emulador'         => 'FICHAS- EMULADORES- FUSIBLES',
        'cargador'         => 'CARGADORES PARA ELÉCTRICOS E HÍBRIDOS',
    ];

    public function name(): string
    {
        return 'mock';
    }

    public function isAvailable(): bool
    {
        return true;
    }

    public function chat(array $messages, array $tools = [], array $options = []): AiResponse
    {
        $started = microtime(true);
        $last    = $this->lastUserMessage($messages);

        return new AiResponse(
            text: $this->composeReply($messages, $last),
            toolCalls: [],
            usage: new AiUsage(inputTokens: $this->roughTokens($messages), outputTokens: 48),
            provider: 'mock',
            model: 'mock-advisor-v1',
            latencyMs: (microtime(true) - $started) * 1000,
        );
    }

    /**
     * Visión simulada.
     *
     * Sin modelo real no se puede mirar la foto, así que el mock es honesto:
     * declara baja confianza, no inventa un tipo de pieza a partir de la nada y
     * usa el contexto textual que ya haya dado el cliente. Ese comportamiento
     * es exactamente el que queremos que tenga el sistema ante una foto
     * ambigua, así que el escenario "foto insuficiente" queda demostrable.
     */
    public function analyzeImage(string $imagePath, string $context = ''): ImageAnalysis
    {
        if (! is_file($imagePath)) {
            return ImageAnalysis::unusable('No se pudo leer la imagen.');
        }

        $size = @getimagesize($imagePath);

        if ($size === false) {
            return ImageAnalysis::unusable('El archivo no es una imagen válida.');
        }

        [$width, $height] = $size;

        // Una foto muy chica no da para distinguir una pieza.
        if ($width < 200 || $height < 200) {
            return ImageAnalysis::unusable('La imagen es demasiado chica para distinguir la pieza.');
        }

        $hints      = $this->categoryHints($context);
        $attributes = $this->extractAttributes($context);
        $codes      = $this->extractCodes($context);

        return new ImageAnalysis(
            partType: $hints[0] ?? null,
            // Deliberadamente por debajo del umbral de "alta": una foto sola
            // nunca alcanza para afirmar una pieza.
            confidence: $hints === [] ? 0.28 : 0.55,
            detectedText: [],
            visibleCodes: $codes,
            attributes: $attributes,
            categoryHints: $hints,
            brandGuess: $this->extractBrand($context),
            description: $hints === []
                ? 'Se ve una pieza de electricidad del automotor, pero no alcanza para determinar el rubro.'
                : 'Por la forma podría corresponder a ' . mb_strtolower((string) $hints[0]) . '.',
            imageUsable: true,
            usage: new AiUsage(inputTokens: 0, outputTokens: 0, imagesAnalyzed: 1),
        );
    }

    /**
     * Interpretación estructurada por heurística.
     *
     * Cubre el schema de extracción que usa el orquestador: intención, rubros
     * candidatos, atributos y qué falta.
     */
    public function structuredOutput(string $prompt, array $schema, array $options = []): array
    {
        $hints      = $this->categoryHints($prompt);
        $attributes = $this->extractAttributes($prompt);
        $codes      = $this->extractCodes($prompt);
        $brand      = $this->extractBrand($prompt);

        return [
            'intent'              => $this->classifyIntent($prompt),
            'category_candidates' => array_map(
                static fn (string $name, int $i): array => [
                    'category_name' => $name,
                    'confidence'    => round(0.8 - ($i * 0.15), 2),
                ],
                $hints,
                array_keys($hints)
            ),
            'extracted_attributes' => array_merge($attributes, array_filter([
                'brand'        => $brand,
                'visible_code' => $codes[0] ?? null,
            ])),
            'next_required_information' => $this->missingInformation($hints, $attributes, $codes, $brand),
        ];
    }

    public function embed(string $text): array
    {
        // Embedding determinístico y estable para poder testear el pipeline
        // semántico sin proveedor. No tiene ningún significado semántico real.
        $vector = array_fill(0, 64, 0.0);

        foreach (preg_split('/\W+/u', mb_strtolower($text)) ?: [] as $token) {
            if ($token === '') {
                continue;
            }
            $vector[crc32($token) % 64] += 1.0;
        }

        $norm = sqrt(array_sum(array_map(static fn (float $v): float => $v * $v, $vector))) ?: 1.0;

        return array_map(static fn (float $v): float => $v / $norm, $vector);
    }

    // -----------------------------------------------------------------
    // Heurísticas de interpretación
    // -----------------------------------------------------------------

    public function classifyIntent(string $text): string
    {
        $t = $this->fold($text);

        return match (true) {
            $this->containsAny($t, ['cuanto sale', 'cuanto cuesta', 'precio', 'cuanto me queda', 'cotiza'])
                => 'price_inquiry',
            $this->containsAny($t, ['compre', 'compro siempre', 'la otra vez', 'ultima vez', 'historial', 'de siempre'])
                => 'reorder_from_history',
            $this->containsAny($t, ['equivalente', 'equivalencia', 'reemplazo', 'parecido', 'alternativa'])
                => 'equivalence_lookup',
            $this->containsAny($t, ['asesor', 'humano', 'hablar con alguien', 'vendedor'])
                => 'human_assistance',
            $this->containsAny($t, ['stock', 'disponible', 'tenes en', 'hay stock'])
                => 'availability_inquiry',
            default => 'product_identification',
        };
    }

    /** @return list<string> nombres de rubro sugeridos, ordenados */
    public function categoryHints(string $text): array
    {
        $t     = $this->fold($text);
        $hits  = [];

        foreach (self::PART_KEYWORDS as $keyword => $category) {
            if (str_contains($t, $keyword)) {
                // La palabra más larga que matchea gana: "portaescobilla" antes
                // que "escobilla".
                $hits[$category] = max($hits[$category] ?? 0, mb_strlen($keyword));
            }
        }

        arsort($hits);

        return array_slice(array_keys($hits), 0, 3);
    }

    /** @return array<string,string> clave canónica => valor */
    public function extractAttributes(string $text): array
    {
        $attributes = [];
        $t          = $this->fold($text);

        /*
         * Pares "Etiqueta: valor".
         *
         * Es la forma que usan las respuestas rápidas del chat ("Largo total:
         * 152") y también cómo escribe mucha gente cuando contesta una
         * pregunta puntual. La etiqueta se resuelve contra el diccionario de
         * atributos, así que funciona para cualquiera de los 74 sin listarlos.
         */
        if (preg_match_all('/([\p{L}\s]{3,40}?)\s*:\s*([^\n,;]{1,40})/u', $text, $pairs, PREG_SET_ORDER)) {
            foreach ($pairs as $pair) {
                $key = LegacyAttributeMap::resolveTerm(trim($pair[1]));

                // "Marca" resuelve al slot 50, pero a nivel de producto la marca
                // vive en `productos.marca`. Se usa el campo real, que es el que
                // filtra bien.
                $key = match ($key) {
                    'attr_brand' => 'brand',
                    default      => $key,
                };

                if ($key !== null && trim($pair[2]) !== '') {
                    $attributes[$key] = trim($pair[2]);
                }
            }
        }

        // "12v", "24 v"
        if (preg_match('/\b(\d{1,2})\s*v\b/', $t, $m)) {
            $attributes['voltage'] = $m[1] . 'v';
        }

        // "75a", "90 amp"
        if (preg_match('/\b(\d{2,3})\s*(?:a|amp|amperes?)\b/', $t, $m)) {
            $attributes['amperes'] = $m[1];
        }

        // "28 mm", "de 88.8mm", "diametro 125"
        if (preg_match('/\b(\d{1,3}(?:[.,]\d{1,2})?)\s*mm\b/', $t, $m)) {
            $attributes['diameter'] = str_replace(',', '.', $m[1]);
        } elseif (preg_match('/\bdiametro\s*(?:de\s*)?(\d{1,3}(?:[.,]\d{1,2})?)/', $t, $m)) {
            $attributes['diameter'] = str_replace(',', '.', $m[1]);
        }

        if (preg_match('/\blargo\s*(?:de\s*)?(\d{1,3}(?:[.,]\d{1,2})?)/', $t, $m)) {
            $attributes['total_length'] = str_replace(',', '.', $m[1]);
        }

        // "9 estrias", "10 dientes"
        foreach (['estrias' => 'splines', 'dientes' => 'teeth', 'ranuras' => 'slots', 'pines' => 'pins'] as $word => $key) {
            if (preg_match('/\b(\d{1,2})\s*' . $word . '\b/', $t, $m)) {
                $attributes[$key] = $m[1];
            }
        }

        return $attributes;
    }

    /** @return list<string> */
    public function extractCodes(string $text): array
    {
        $codes = [];

        if (preg_match_all('/\b([A-Z]{2,4}\s?\d{4,8})\b/i', $text, $m)) {
            foreach ($m[1] as $code) {
                $codes[] = trim($code);
            }
        }

        if (preg_match_all('/\b(\d{4,10})\b/', $text, $m)) {
            foreach ($m[1] as $code) {
                $codes[] = $code;
            }
        }

        return array_values(array_unique($codes));
    }

    public function extractBrand(string $text): ?string
    {
        $t = $this->fold($text);

        foreach (self::BRANDS as $brand) {
            if (str_contains($t, $this->fold($brand))) {
                return $brand;
            }
        }

        return null;
    }

    /** @return list<string> */
    private function missingInformation(array $hints, array $attributes, array $codes, ?string $brand): array
    {
        $missing = [];

        if ($codes === []) {
            $missing[] = 'visible_code';
        }
        if ($hints === []) {
            $missing[] = 'category';
        }
        if ($brand === null) {
            $missing[] = 'brand';
        }
        if (! isset($attributes['diameter']) && ! isset($attributes['total_length'])) {
            $missing[] = 'dimensions';
        }

        return $missing;
    }

    /**
     * Redacción. Tono BMH: argentino, directo, sin celebración ni emojis.
     *
     * El orquestador ya resolvió qué decir; acá se le da forma. Cuando hay
     * datos duros en el contexto (candidatos, precio), se usan tal cual: el
     * mock nunca inventa un número.
     */
    private function composeReply(array $messages, ?AiMessage $last): string
    {
        $toolPayloads = $this->toolPayloads($messages);
        $text         = $last?->content ?? '';

        if (isset($toolPayloads['request_human_assistance'])) {
            return (string) config('bmh.handoff.message');
        }

        if (isset($toolPayloads['get_customer_price'])) {
            $price = $toolPayloads['get_customer_price'];

            if (($price['status'] ?? '') !== 'verified') {
                return 'El precio de esa referencia necesita que lo confirme un asesor. No te quiero pasar un número que después no sea el correcto.';
            }

            return sprintf(
                'Con tu condición comercial actual el precio es $%s + IVA.',
                number_format((float) $price['net_price'], 2, ',', '.')
            );
        }

        if (isset($toolPayloads['search_products'])) {
            return $this->replyForCandidates($toolPayloads['search_products']);
        }

        if ($this->classifyIntent($text) === 'reorder_from_history') {
            return 'Busco en tus compras anteriores y te muestro lo que aparece.';
        }

        $hints = $this->categoryHints($text);

        if ($hints !== []) {
            return sprintf('Por lo que me contás parece un %s. Lo busco en el catálogo.', mb_strtolower($hints[0]));
        }

        return 'Contame un poco más: si tenés el código grabado en la pieza o una foto, con eso lo ubico.';
    }

    private function replyForCandidates(array $payload): string
    {
        $count = (int) ($payload['total'] ?? count($payload['candidates'] ?? []));

        if ($count === 0) {
            return 'Con esos datos no encuentro nada en el catálogo. ¿Tenés algún código grabado en la pieza?';
        }

        if (isset($payload['next_question']['label'])) {
            return sprintf(
                'Encontré %d opciones parecidas. Para achicarlo, ¿sabés %s?',
                $count,
                mb_strtolower((string) $payload['next_question']['label'])
            );
        }

        // Con una coincidencia muy alta se afirma, aunque haya otras cerca:
        // preguntar "¿cuál es?" teniendo un match de código sería tonto.
        $topBand = $payload['candidates'][0]['confidence'] ?? 0;

        if ($topBand >= (float) config('bmh.confidence.very_high')) {
            return $count === 1
                ? 'Encontré esta pieza.'
                : sprintf('Encontré esta pieza. Te dejo %d referencias relacionadas por si acaso.', $count - 1);
        }

        if ($count === 1) {
            return 'Encontré una coincidencia fuerte.';
        }

        return sprintf('Encontré %d opciones que coinciden. ¿Cuál es?', $count);
    }

    /** @return array<string, array> última salida de cada tool */
    private function toolPayloads(array $messages): array
    {
        $payloads = [];

        foreach ($messages as $message) {
            if ($message->role === AiMessage::ROLE_TOOL && $message->toolName !== null) {
                $decoded = json_decode($message->content, true);
                if (is_array($decoded)) {
                    $payloads[$message->toolName] = $decoded;
                }
            }
        }

        return $payloads;
    }

    private function lastUserMessage(array $messages): ?AiMessage
    {
        foreach (array_reverse($messages) as $message) {
            if ($message->role === AiMessage::ROLE_USER) {
                return $message;
            }
        }

        return null;
    }

    private function roughTokens(array $messages): int
    {
        $characters = array_sum(array_map(
            static fn (AiMessage $m): int => mb_strlen($m->content),
            $messages
        ));

        return (int) ceil($characters / 4);
    }

    private function containsAny(string $haystack, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function fold(string $value): string
    {
        $value = mb_strtolower(trim($value));

        return strtr($value, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n', 'ü' => 'u',
        ]);
    }
}
