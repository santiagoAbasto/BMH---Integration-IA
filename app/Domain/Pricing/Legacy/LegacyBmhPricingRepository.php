<?php

declare(strict_types=1);

namespace App\Domain\Pricing\Legacy;

use App\Domain\Pricing\Contracts\PricingRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final class LegacyBmhPricingRepository implements PricingRepositoryInterface
{
    public function taxRate(): float
    {
        return Cache::remember('bmh:legacy:tax-rate', 3600, function (): float {
            $row = DB::connection('mysql_legacy')->table('impuestos')
                ->where('id', config('bmh.pricing.iva_id', 1))
                ->first();

            return $row === null ? 0.0 : (float) $row->porcentaje;
        });
    }

    public function productDiscount(int $productId): float
    {
        $value = DB::connection('mysql_legacy')->table('productos')
            ->where('id', $productId)
            ->value('descuento');

        return $this->percent($value);
    }

    public function categoryModifiers(int $categoryId): array
    {
        $row = DB::connection('mysql_legacy')->table('categorias')
            ->select('descuento', 'aumento')
            ->where('id', $categoryId)
            ->first();

        return [
            'descuento' => $row === null ? 0.0 : $this->percent($row->descuento),
            'aumento'   => $row === null ? 0.0 : $this->percent($row->aumento),
        ];
    }

    public function bonificationTiers(): array
    {
        return Cache::remember('bmh:legacy:bonification-tiers', 3600, function (): array {
            return DB::connection('mysql_legacy')->table('bonificaciones')
                ->orderBy('desde')
                ->get()
                ->map(static fn (object $r): array => [
                    'desde'      => (float) $r->desde,
                    'hasta'      => (float) $r->hasta,
                    'porcentaje' => (float) $r->porcentaje,
                ])
                ->all();
        });
    }

    public function listPrice(int $productId): ?float
    {
        $value = DB::connection('mysql_legacy')->table('productos')
            ->where('id', $productId)
            ->value('precio');

        return $value === null ? null : (float) $value;
    }

    /** `descuento` y `aumento` son varchar en la base. Pueden venir vacíos o con coma. */
    private function percent(mixed $value): float
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return 0.0;
        }

        return (float) str_replace(',', '.', $value);
    }
}
