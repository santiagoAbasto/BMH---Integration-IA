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
        public ?AiUsage $usage = null,
    ) {
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

    public function provenance(): Provenance
    {
        return Provenance::ai(Provenance::AI_VISION, $this->confidence);
    }

    public function hasCode(): bool
    {
        return $this->visibleCodes !== [];
    }

    public function jsonSerialize(): array
    {
        return [
            'part_type'      => $this->partType,
            'confidence'     => round($this->confidence, 3),
            'visible_codes'  => $this->visibleCodes,
            'detected_text'  => $this->detectedText,
            'attributes'     => $this->attributes,
            'category_hints' => $this->categoryHints,
            'brand_guess'    => $this->brandGuess,
            'description'    => $this->description,
            'usable'         => $this->imageUsable,
            'reason'         => $this->unusableReason,
            'provenance'     => $this->provenance(),
        ];
    }
}
