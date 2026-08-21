<?php

declare(strict_types=1);

namespace App\Domain\Catalog\Contracts;

use App\Domain\Catalog\DTO\CategoryView;
use App\Domain\Catalog\DTO\ProductView;

/**
 * Acceso al catálogo. La implementación legacy es la única que conoce el
 * esquema viejo; el resto del sistema programa contra esta interfaz.
 */
interface CatalogRepositoryInterface
{
    public function find(int $id): ?ProductView;

    /**
     * @param  list<int> $ids
     * @return list<ProductView>
     */
    public function findMany(array $ids): array;

    /**
     * Búsqueda exacta por código. Devuelve una LISTA porque `codigo` no es
     * único en la base legacy (11 colisiones verificadas).
     *
     * @return list<ProductView>
     */
    public function findByCode(string $code): array;

    /**
     * Búsqueda por código normalizado (sin espacios, guiones ni puntos).
     *
     * @return list<ProductView>
     */
    public function findByNormalizedCode(string $code): array;

    /**
     * Coincidencia parcial de código, para cuando el cliente manda un fragmento.
     *
     * @return list<ProductView>
     */
    public function searchByPartialCode(string $fragment, int $limit = 25): array;

    /**
     * Búsqueda por equivalencia declarada (código de otro fabricante).
     *
     * @return list<ProductView>
     */
    public function findByEquivalence(string $code, int $limit = 25): array;

    /**
     * Búsqueda por palabras sobre nombre / marca / modelo.
     *
     * @param  list<int> $categoryIds filtro opcional de rubro
     * @return list<ProductView>
     */
    public function searchByKeywords(string $query, array $categoryIds = [], int $limit = 50): array;

    /**
     * Filtro estructurado por atributos técnicos.
     *
     * @param  array<string, string> $attributes clave canónica => valor
     * @param  list<int>             $categoryIds
     * @return list<ProductView>
     */
    public function searchByAttributes(array $attributes, array $categoryIds = [], int $limit = 50): array;

    /** @return list<CategoryView> */
    public function categories(): array;

    public function category(int $id): ?CategoryView;

    /** Códigos que aparecen en más de un producto. @return array<string, list<int>> */
    public function duplicateCodes(): array;
}
