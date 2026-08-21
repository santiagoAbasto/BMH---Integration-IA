<?php

declare(strict_types=1);

namespace App\Domain\Support;

/**
 * De dónde salió un dato.
 *
 * La distinción es crítica: un valor con `source = producto_caracteristica`
 * es un HECHO de la base de BMH. Un valor con `source = ai_vision` es una
 * INFERENCIA de un modelo. El asistente tiene permitido afirmar el primero y
 * está obligado a matizar el segundo.
 *
 * @see docs/ai-architecture.md §"Data provenance"
 */
final readonly class Provenance implements \JsonSerializable
{
    public const DB_PRODUCT        = 'productos';
    public const DB_LEGACY_SLOT    = 'productos.columna_n';
    public const DB_CHARACTERISTIC = 'producto_caracteristica';
    public const DB_CATEGORY       = 'categorias';
    public const DB_IMAGE          = 'imagenes';
    public const DB_ORDER          = 'pedido_producto';
    public const PRICING_ENGINE    = 'pricing_engine';
    public const AI_VISION         = 'ai_vision';
    public const AI_TEXT           = 'ai_text';
    public const USER_STATED       = 'user_stated';
    public const UNVERIFIED        = 'stock_semantics_unverified';

    private function __construct(
        public string $source,
        public ?string $sourceDetail,
        public int|string|null $sourceId,
        public float $confidence,
    ) {
    }

    /** Dato leído de la base. Confianza 1.0 por definición: es lo que dice BMH. */
    public static function database(
        string $table,
        int|string|null $id = null,
        ?string $detail = null,
    ): self {
        return new self($table, $detail, $id, 1.0);
    }

    /** Dato inferido por un modelo. Nunca llega a 1.0. */
    public static function ai(string $source, float $confidence, ?string $detail = null): self
    {
        return new self($source, $detail, null, max(0.0, min(0.99, $confidence)));
    }

    /** Dato que dijo el cliente. Alto pero no infalible: la gente se confunde. */
    public static function userStated(float $confidence = 0.9): self
    {
        return new self(self::USER_STATED, null, null, $confidence);
    }

    /** Dato cuya semántica no está confirmada. Nunca se afirma. */
    public static function unverified(string $reason): self
    {
        return new self(self::UNVERIFIED, $reason, null, 0.0);
    }

    /** ¿Se puede afirmar como hecho ante el cliente? */
    public function isFactual(): bool
    {
        return $this->confidence >= 1.0
            && ! in_array($this->source, [self::AI_VISION, self::AI_TEXT, self::UNVERIFIED], true);
    }

    public function isInference(): bool
    {
        return in_array($this->source, [self::AI_VISION, self::AI_TEXT], true);
    }

    public function jsonSerialize(): array
    {
        return array_filter([
            'source'        => $this->source,
            'source_detail' => $this->sourceDetail,
            'source_id'     => $this->sourceId,
            'confidence'    => $this->confidence,
            'factual'       => $this->isFactual(),
        ], static fn ($v) => $v !== null);
    }
}
