<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Producto;
use Illuminate\Support\Collection;

final class CatalogFilterOptions
{
    /**
     * Returns only the data needed to populate the brand/model filters.
     * Loading complete product models here can exceed PHP's memory limit.
     *
     * @return array<string, list<string>>
     */
    public function brandsWithModels(): array
    {
        return Producto::query()
            ->select(['marca', 'modelo'])
            ->whereNotNull('marca')
            ->where('marca', '<>', '')
            ->distinct()
            ->orderBy('marca')
            ->orderBy('modelo')
            ->get()
            ->groupBy('marca')
            ->sortKeys()
            ->map(static function (Collection $products): array {
                return $products
                    ->pluck('modelo')
                    ->filter(static fn ($model): bool => is_string($model) && trim($model) !== '')
                    ->map(static fn (string $model): string => trim($model))
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();
            })
            ->all();
    }
}
