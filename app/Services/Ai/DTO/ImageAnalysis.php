<?php

declare(strict_types=1);

namespace App\Services\Ai\DTO;

use App\Domain\Support\Provenance;

/**
 * Lo que el modelo CREE ver en una foto.
 *
 * Nada de esto es un hecho. Todo lo que sale de acá lleva provenance
 * `ai_vision` y se usa únicamente para armar filtros de búsqueda: la
 * identificación final siempre la confirma la base.
 *
 * El texto detectado por OCR es la parte más valiosa: un código legible en la
 * pieza vale muchísimo más que un parecido de forma.
 */
final readonly class ImageAnalysis implements \JsonSerializable
{
    /**
     * @param list<string>          $detectedText  texto/OCR crudo visible
     * @param list<string>          $visibleCodes  códigos plausibles extraídos
     * @param array<string,string>  $attributes    clave canónica => valor observado
     * @param list<string>          $categoryHints nombres de rubro sugeridos
     */
    public function __construct(
        public ?string $partType,
        public float $confidence,
        public array $detectedText = [],
        public array $visibleCodes = [],
        public array $attributes = [],
        public array $categoryHints = [],
        public ?string $brandGuess = null,
        public ?string $description = null,
        public bool $imageUsable = true,
        public ?string $unusableReason = null,
        /** Qué tan segura fue la LECTURA del texto, aparte del reconocimiento. */
        public float $textConfidence = 0.0,
        public ?AiUsage $usage = null,
        /** part_photo | catalog_screenshot | other. */
        public string $imageKind = self::KIND_PART_PHOTO,
        /**
         * Sólo en una captura del catálogo: el código del producto principal.
         * Los códigos de "relacionados" o de otras tarjetas no son el pedido.
         */
        public ?string $catalogCode = null,
        /**
         * El nombre/título del producto si se lee impreso en la imagen (aunque
         * esté cortado). Sin precios, cotas ni rótulos de interfaz.
         */
        public ?string $productText = null,
    ) {
    }

    public const KIND_PART_PHOTO = 'part_photo';
    public const KIND_CATALOG_SCREENSHOT = 'catalog_screenshot';
    public const KIND_OTHER = 'other';

    public function isCatalogScreenshot(): bool
    {
        return $this->imageKind === self::KIND_CATALOG_SCREENSHOT;
    }

    /**
     * Los códigos con los que buscar. En una captura con producto principal
     * claro, sólo ese: el resto del texto de la página (relacionados, carrito)
     * traería productos que el cliente no pidió.
     *
     * @return list<string>
     */
    public function searchCodes(): array
    {
        if ($this->isCatalogScreenshot() && $this->catalogCode !== null) {
            return [$this->catalogCode];
        }

        return $this->visibleCodes;
    }

    public static function unusable(string $reason): self
    {
        return new self(
            partType: null,
            confidence: 0.0,
            imageUsable: false,
            unusableReason: $reason,
        );
    }

    /**
     * ¿La lectura de código es lo bastante sólida para buscar por código?
     *
     * Un código legible es un match DURO contra `productos.codigo`, así que el
     * umbral es bajo a propósito: si existe en el catálogo, la base lo confirma;
     * si no existe, no devuelve nada y no se pierde nada por haber probado.
     */
    public function hasReliableCode(): bool
    {
        return $this->searchCodes() !== [] && $this->textConfidence >= 0.3;
    }

    public function provenance(): Provenance
    {
        return Provenance::ai(Provenance::AI_VISION, $this->confidence);
    }

    public function hasCode(): bool
    {
        return $this->searchCodes() !== [];
    }

    public function jsonSerialize(): array
    {
        return [
            'part_type'      => $this->partType,
            'confidence'     => round($this->confidence, 3),
            'visible_codes'  => $this->visibleCodes,
            'detected_text'  => $this->detectedText,
            'text_confidence'=> round($this->textConfidence, 3),
            'attributes'     => $this->attributes,
            'category_hints' => $this->categoryHints,
            'brand_guess'    => $this->brandGuess,
            'description'    => $this->description,
            'usable'         => $this->imageUsable,
            'reason'         => $this->unusableReason,
            'image_kind'     => $this->imageKind,
            'catalog_code'   => $this->catalogCode,
            'product_text'   => $this->productText,
            'provenance'     => $this->provenance(),
        ];
    }
}
