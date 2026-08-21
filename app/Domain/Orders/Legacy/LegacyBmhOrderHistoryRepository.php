<?php

declare(strict_types=1);

namespace App\Domain\Orders\Legacy;

use App\Domain\Orders\Contracts\OrderHistoryRepositoryInterface;
use App\Domain\Orders\DTO\PurchasedLine;
use Illuminate\Support\Facades\DB;

/**
 * Historial de compras.
 *
 * `pedidos.cliente_id` va SIEMPRE en el WHERE y siempre con el id que resolvió
 * el backend desde la sesión. No hay ningún método que permita pedir el
 * historial de otro cliente: el aislamiento es estructural, no una policy que
 * alguien pueda olvidarse de aplicar.
 */
final class LegacyBmhOrderHistoryRepository implements OrderHistoryRepositoryInterface
{
    public function linesForCustomer(int $customerId, int $limit = 100): array
    {
        return $this->query($customerId)->limit($limit)->get()->map($this->toLine(...))->all();
    }

    public function linesForCustomerInCategory(int $customerId, int $categoryId, int $limit = 25): array
    {
        return $this->query($customerId)
            ->where('pr.categoria_id', $categoryId)
            ->limit($limit)
            ->get()
            ->map($this->toLine(...))
            ->all();
    }

    public function purchasedProductIds(int $customerId): array
    {
        return DB::connection('mysql_legacy')->table('pedido_producto as pp')
            ->join('pedidos as p', 'p.id', '=', 'pp.pedido_id')
            ->where('p.cliente_id', $customerId)
            ->distinct()
            ->pluck('pp.producto_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    private function query(int $customerId): \Illuminate\Database\Query\Builder
    {
        return DB::connection('mysql_legacy')->table('pedido_producto as pp')
            ->join('pedidos as p', 'p.id', '=', 'pp.pedido_id')
            ->join('productos as pr', 'pr.id', '=', 'pp.producto_id')
            ->leftJoin('categorias as c', 'c.id', '=', 'pr.categoria_id')
            ->where('p.cliente_id', $customerId)
            ->orderByDesc('p.created_at')
            ->select(
                'p.id as pedido_id',
                'p.fecha',
                'p.created_at',
                'pp.cantidad',
                'pp.precio_unitario',
                'pp.precio_descontado',
                'pr.id as producto_id',
                'pr.codigo',
                'pr.nombre',
                'pr.categoria_id',
                'c.nombre as categoria',
            );
    }

    private function toLine(object $row): PurchasedLine
    {
        return new PurchasedLine(
            orderId: (int) $row->pedido_id,
            productId: (int) $row->producto_id,
            productCode: trim((string) $row->codigo),
            productName: trim((string) $row->nombre),
            categoryId: $row->categoria_id === null ? null : (int) $row->categoria_id,
            categoryName: $row->categoria === null ? null : trim((string) $row->categoria),
            quantity: $this->toFloat($row->cantidad),
            historicUnitPrice: $this->toFloat($row->precio_unitario),
            historicDiscountedPrice: $this->toFloat($row->precio_descontado),
            orderDate: $row->created_at ?? $row->fecha ?? null,
        );
    }

    /**
     * `pedido_producto` guarda los precios como varchar con formato argentino
     * ("15.000,50"). Hay que desarmarlo antes de convertir.
     */
    private function toFloat(mixed $value): float
    {
        $value = trim((string) ($value ?? ''));

        if ($value === '') {
            return 0.0;
        }

        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        }

        return (float) preg_replace('/[^0-9.\-]/', '', $value);
    }
}
