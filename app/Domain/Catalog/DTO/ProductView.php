<?php

declare(strict_types=1);

namespace App\Domain\Catalog\DTO;

use App\Domain\Support\Provenance;

/**
 * Representación limpia de un producto del catálogo.
 *
 * Es un modelo de LECTURA. No reemplaza la tabla `productos`: la interpreta.
 * Ningún componente de React ni ninguna tool de IA ve nunca otra cosa que esto.
 */
final readonly class ProductView implements \JsonSerializable
{
    /**
     * @param list<AttributeValue> $attributes
     * @param list<CrossReference> $equivalences
     * @param list<CrossReference> $relatedParts
     * @param list<CrossReference> $applications  aplicaciones nuevas (tabla `aplicaciones`)
     * @param list<string>         $images       nombres de archivo verificados
     */
    public function __construct(
        public int $id,
        public string $code,
        public string $name,
        public ?CategoryView $category,
        public ?string $brand,
        public ?string $model,
        public ProductCondition $condition,
        public array $attributes,
        public array $equivalences,
        public array $relatedParts,
        public array $applications,
        public array $images,
        public ?float $listPrice,
        public bool $hasDuplicateCode,
        public string $searchableText,
    ) {
    }

    public function attribute(string $key): ?AttributeValue
    {
        foreach ($this->attributes as $attribute) {
            if ($attribute->key === $key) {
                return $attribute;
            }
        }

        return null;
    }

    public function attributeValue(string $key): ?string
    {
        return $this->attribute($key)?->value;
    }

    /** @return list<string> claves de atributos con valor cargado */
    public function knownAttributeKeys(): array
    {
        return array_map(static fn (AttributeValue $a): string => $a->key, $this->attributes);
    }

    public function primaryImage(): ?string
    {
        return $this->images[0] ?? null;
    }

    /**
     * Vista mínima para mandarle a la IA. Sin precio (lo calcula el
     * PricingEngine), sin datos que el modelo no necesite para razonar.
     */
    public function forAiTool(): array
    {
        return [
            'id'         => $this->id,
            'code'       => $this->code,
            'name'       => $this->name,
            'category'   => $this->category?->name,
            'brand'      => $this->brand,
            'model'      => $this->model,
            'condition'  => $this->condition->value,
            'attributes' => array_reduce(
                $this->attributes,
                static function (array $carry, AttributeValue $a): array {
                    $carry[$a->label] = $a->displayValue();

                    return $carry;
                },
                []
            ),
            'equivalences'  => array_map(static fn (CrossReference $r): string => $r->code, $this->equivalences),
            'related_parts' => array_map(static fn (CrossReference $r): string => $r->code, $this->relatedParts),
            'applications'  => array_map(static fn (CrossReference $r): string => $r->code, $this->applications),
            'has_image'     => $this->images !== [],
            'duplicate_code'=> $this->hasDuplicateCode,
        ];
    }

    public function jsonSerialize(): array
    {
        return [
            'id'            => $this->id,
            'code'          => $this->code,
            'name'          => $this->name,
            'category'      => $this->category,
            'brand'         => $this->brand,
            'model'         => $this->model,
            'condition'     => [
                'value' => $this->condition->value,
                'label' => $this->condition->label(),
            ],
            'attributes'    => $this->attributes,
            'equivalences'  => $this->equivalences,
            'related_parts' => $this->relatedParts,
            'applications'  => $this->applications,
            'images'        => $this->images,
            'duplicate_code'=> $this->hasDuplicateCode,
            'provenance'    => Provenance::database('productos', $this->id),
        ];
    }
}
