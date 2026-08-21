<?php

declare(strict_types=1);

namespace App\Domain\Catalog\DTO;

use App\Domain\Support\Provenance;

/**
 * Un atributo técnico de un producto, con su origen.
 *
 * Nunca se pierde de dónde vino: `sourceField` conserva la columna legacy real
 * para poder auditar y para la migración futura, sin que nadie aguas arriba
 * tenga que conocerla.
 */
final readonly class AttributeValue implements \JsonSerializable
{
    public function __construct(
        public string $key,
        public string $label,
        public string $value,
        public string $type,
        public ?string $unit,
        public Provenance $provenance,
        public ?string $sourceField = null,
        public ?string $sourceTable = null,
    ) {
    }

    /** Valor numérico si el atributo es dimensional o eléctrico. */
    public function numeric(): ?float
    {
        if (! preg_match('/-?\d+(?:[.,]\d+)?/', $this->value, $m)) {
            return null;
        }

        return (float) str_replace(',', '.', $m[0]);
    }

    public function displayValue(): string
    {
        if ($this->unit !== null && $this->numeric() !== null
            && ! str_contains(mb_strtolower($this->value), mb_strtolower($this->unit))) {
            return trim($this->value) . ' ' . $this->unit;
        }

        return trim($this->value);
    }

    public function jsonSerialize(): array
    {
        return [
            'key'        => $this->key,
            'label'      => $this->label,
            'value'      => $this->displayValue(),
            'raw'        => $this->value,
            'type'       => $this->type,
            'unit'       => $this->unit,
            'provenance' => $this->provenance,
        ];
    }
}
