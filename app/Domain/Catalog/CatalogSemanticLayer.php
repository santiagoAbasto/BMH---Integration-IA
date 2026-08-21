<?php

declare(strict_types=1);

namespace App\Domain\Catalog;

use App\Domain\Catalog\DTO\AttributeValue;
use App\Domain\Catalog\DTO\CategoryView;
use App\Domain\Catalog\DTO\CrossReference;
use App\Domain\Catalog\DTO\ProductCondition;
use App\Domain\Catalog\DTO\ProductView;
use App\Domain\Support\Provenance;

/**
 * Convierte filas legacy en ProductView.
 *
 * Es el traductor del anti-corruption layer. Sabe de `columna_N`, de valores
 * "-" que significan "no aplica" y de que `descripcion` está vacía en el 99,6 %
 * de los productos. Nada de eso sale de esta clase.
 *
 * No sobreescribe el dato original: lo interpreta y conserva `source_field` /
 * `source_table` en cada atributo para poder auditarlo después.
 *
 * @see docs/database-discovery.md §2
 */
final class CatalogSemanticLayer
{
    /**
     * @param object            $product        fila de `productos`
     * @param object|null       $categoryLabels fila de `categorias` (etiquetas de slot)
     * @param list<object>      $characteristics filas de producto_caracteristica + nombre
     * @param list<string>      $images         nombres de archivo ya verificados
     * @param bool              $duplicateCode  si el código colisiona con otro producto
     */
    public function toProductView(
        object $product,
        ?object $categoryLabels,
        array $characteristics = [],
        array $images = [],
        bool $duplicateCode = false,
        int $categoryProductCount = 0,
    ): ProductView {
        $attributes   = $this->mapLegacySlots($product, $categoryLabels);
        $equivalences = $this->extractEquivalences($product, $categoryLabels, $characteristics);
        $relatedParts = $this->extractRelatedParts($characteristics);

        $category = $categoryLabels === null ? null : new CategoryView(
            id: (int) $categoryLabels->id,
            name: $this->clean((string) $categoryLabels->nombre),
            alias: $categoryLabels->alias ?? null,
            attributeSlots: $this->labelledSlots($categoryLabels),
            productCount: $categoryProductCount,
        );

        return new ProductView(
            id: (int) $product->id,
            code: trim((string) $product->codigo),
            name: $this->clean((string) $product->nombre),
            category: $category,
            brand: $this->nullIfBlank($product->marca ?? null),
            model: $this->nullIfBlank($product->modelo ?? null),
            condition: ProductCondition::fromLegacy($product->estado ?? null),
            attributes: $attributes,
            equivalences: $equivalences,
            relatedParts: $relatedParts,
            images: $images,
            listPrice: isset($product->precio) ? (float) $product->precio : null,
            hasDuplicateCode: $duplicateCode,
            searchableText: $this->buildSearchableText($product, $category, $attributes, $equivalences),
        );
    }

    /**
     * El corazón: `productos.columna_N` (valor) × `categorias.columna_N` (etiqueta).
     *
     * Sólo se emite el atributo cuando LOS DOS lados están cargados. Un valor
     * sin etiqueta es un dato huérfano y no se puede interpretar; se reporta en
     * `bmh:data-audit` pero no se le muestra a nadie.
     *
     * @return list<AttributeValue>
     */
    public function mapLegacySlots(object $product, ?object $categoryLabels): array
    {
        if ($categoryLabels === null) {
            return [];
        }

        $attributes = [];

        foreach (LegacyAttributeMap::slots() as $slot) {
            $valueColumn = LegacyAttributeMap::column($slot);
            $labelColumn = LegacyAttributeMap::categoryLabelColumn($slot);

            $value = $product->{$valueColumn} ?? null;
            $label = $categoryLabels->{$labelColumn} ?? null;

            if ($this->isBlank($value) || $this->isBlank($label)) {
                continue;
            }
            if (CrossReference::isPlaceholder((string) $value)) {
                continue;
            }

            $attributes[] = new AttributeValue(
                key: (string) LegacyAttributeMap::key($slot),
                // La etiqueta real de la categoría manda sobre la del diccionario:
                // si BMH la renombra en la base, se refleja sin tocar código.
                label: $this->clean((string) $label),
                value: $this->clean((string) $value),
                type: LegacyAttributeMap::type($slot),
                unit: LegacyAttributeMap::unit($slot),
                provenance: Provenance::database(
                    Provenance::DB_LEGACY_SLOT,
                    (int) $product->id,
                    $valueColumn,
                ),
                sourceField: $valueColumn,
                sourceTable: 'productos',
            );
        }

        return $attributes;
    }

    /**
     * Equivalencias, desde las dos fuentes reales.
     *
     * La tabla `equivalencias` tiene 13 filas y es una pista falsa: las
     * equivalencias de verdad están en el EAV moderno y en los slots
     * cross_reference.
     *
     * @param  list<object> $characteristics
     * @return list<CrossReference>
     */
    public function extractEquivalences(
        object $product,
        ?object $categoryLabels,
        array $characteristics,
    ): array {
        $references = [];

        // Fuente 1: slots legacy de tipo cross_reference con etiqueta de equivalencia/código.
        foreach (LegacyAttributeMap::crossReferenceSlots() as $slot) {
            $key = (string) LegacyAttributeMap::key($slot);
            if (! str_starts_with($key, 'equiv') && ! str_starts_with($key, 'code_')) {
                continue;
            }

            $value = $product->{LegacyAttributeMap::column($slot)} ?? null;
            if ($this->isBlank($value) || CrossReference::isPlaceholder((string) $value)) {
                continue;
            }

            foreach ($this->splitCodes((string) $value) as $code) {
                $references[] = new CrossReference(
                    kind: CrossReference::KIND_EQUIVALENCE,
                    label: (string) LegacyAttributeMap::label($slot),
                    code: $code,
                    provenance: Provenance::database(
                        Provenance::DB_LEGACY_SLOT,
                        (int) $product->id,
                        LegacyAttributeMap::column($slot),
                    ),
                );
            }
        }

        // Fuente 2: EAV moderno, características cuyo nombre habla de equivalencia.
        foreach ($characteristics as $row) {
            $name = $this->clean((string) ($row->caracteristica ?? ''));
            if (! $this->looksLikeEquivalence($name)) {
                continue;
            }
            if (CrossReference::isPlaceholder($row->valor ?? null)) {
                continue;
            }

            foreach ($this->splitCodes((string) $row->valor) as $code) {
                $references[] = new CrossReference(
                    kind: CrossReference::KIND_EQUIVALENCE,
                    label: $name,
                    code: $code,
                    provenance: Provenance::database(
                        Provenance::DB_CHARACTERISTIC,
                        isset($row->id) ? (int) $row->id : null,
                    ),
                );
            }
        }

        return $this->dedupeReferences($references);
    }

    /**
     * Piezas relacionadas: la escobilla / el colector / el regulador que le
     * corresponden a este producto. Es el grafo de piezas escondido en
     * `producto_caracteristica`, y es lo que permite responder "necesito la
     * escobilla de este alternador".
     *
     * @param  list<object> $characteristics
     * @return list<CrossReference>
     */
    public function extractRelatedParts(array $characteristics): array
    {
        $references = [];

        foreach ($characteristics as $row) {
            $name = $this->clean((string) ($row->caracteristica ?? ''));
            if ($name === '' || $this->looksLikeEquivalence($name)) {
                continue;
            }
            if (CrossReference::isPlaceholder($row->valor ?? null)) {
                continue;
            }

            $references[] = new CrossReference(
                kind: CrossReference::KIND_PART,
                label: $name,
                code: $this->clean((string) $row->valor),
                provenance: Provenance::database(
                    Provenance::DB_CHARACTERISTIC,
                    isset($row->id) ? (int) $row->id : null,
                ),
            );
        }

        return $this->dedupeReferences($references);
    }

    /**
     * Texto de búsqueda normalizado que se materializa en
     * `catalog_search_documents`.
     *
     * `productos.descripcion` está vacía en 5.035 de 5.054 filas, así que el
     * texto útil sale del nombre + marca + modelo + atributos + equivalencias.
     *
     * @param list<AttributeValue> $attributes
     * @param list<CrossReference> $equivalences
     */
    public function buildSearchableText(
        object $product,
        ?CategoryView $category,
        array $attributes,
        array $equivalences,
    ): string {
        $lines = [
            'CODIGO: ' . trim((string) $product->codigo),
            'RUBRO: ' . ($category?->name ?? '-'),
            'NOMBRE: ' . $this->clean((string) $product->nombre),
        ];

        if (! $this->isBlank($product->marca ?? null)) {
            $lines[] = 'MARCA: ' . $this->clean((string) $product->marca);
        }
        if (! $this->isBlank($product->modelo ?? null)) {
            $lines[] = 'MODELO: ' . $this->clean((string) $product->modelo);
        }

        if ($attributes !== []) {
            $lines[] = 'CARACTERISTICAS:';
            foreach ($attributes as $attribute) {
                $lines[] = '  ' . $attribute->label . ': ' . $attribute->displayValue();
            }
        }

        if ($equivalences !== []) {
            $lines[] = 'EQUIVALENCIAS:';
            foreach ($equivalences as $reference) {
                $lines[] = '  ' . $reference->label . ': ' . $reference->code;
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Código normalizado para búsqueda tolerante: sin espacios, guiones ni
     * puntos. "REG 40-016" y "reg40016" tienen que encontrarse.
     */
    public static function normalizeCode(string $code): string
    {
        $code = mb_strtoupper(trim($code));

        return preg_replace('/[^A-Z0-9]/u', '', $code) ?? $code;
    }

    /** @return list<int> */
    private function labelledSlots(object $categoryLabels): array
    {
        $slots = [];

        foreach (LegacyAttributeMap::slots() as $slot) {
            $label = $categoryLabels->{LegacyAttributeMap::categoryLabelColumn($slot)} ?? null;
            if (! $this->isBlank($label)) {
                $slots[] = $slot;
            }
        }

        return $slots;
    }

    private function looksLikeEquivalence(string $name): bool
    {
        $upper = mb_strtoupper($name);

        return str_contains($upper, 'EQUIVALENCIA')
            || str_contains($upper, 'ORIGINAL')
            || str_starts_with($upper, 'CODIGO ');
    }

    /** Un campo puede traer varios códigos separados por / , ; o salto de línea. */
    private function splitCodes(string $value): array
    {
        $parts = preg_split('#[/,;\r\n|]+#', $value) ?: [$value];

        $codes = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part !== '' && ! CrossReference::isPlaceholder($part)) {
                $codes[] = $this->clean($part);
            }
        }

        return $codes;
    }

    /**
     * @param  list<CrossReference> $references
     * @return list<CrossReference>
     */
    private function dedupeReferences(array $references): array
    {
        $seen   = [];
        $unique = [];

        foreach ($references as $reference) {
            $fingerprint = $reference->kind . '|' . mb_strtoupper($reference->label) . '|'
                . self::normalizeCode($reference->code);

            if (isset($seen[$fingerprint])) {
                continue;
            }

            $seen[$fingerprint] = true;
            $unique[]           = $reference;
        }

        return $unique;
    }

    private function isBlank(mixed $value): bool
    {
        return $value === null || trim((string) $value) === '';
    }

    private function nullIfBlank(mixed $value): ?string
    {
        return $this->isBlank($value) ? null : $this->clean((string) $value);
    }

    /** Normaliza espacios y arregla el whitespace irregular del dump. */
    private function clean(string $value): string
    {
        $value = str_replace(["\xC2\xA0", "\t"], ' ', $value);

        return trim(preg_replace('/\s+/u', ' ', $value) ?? $value);
    }
}
