<?php

declare(strict_types=1);

namespace App\Domain\Orders\Contracts;

use App\Domain\Orders\DTO\PurchasedLine;

interface OrderHistoryRepositoryInterface
{
    /**
     * Líneas compradas por UN cliente. La implementación filtra por
     * `pedidos.cliente_id` en el SQL: no hay forma de pedir las de otro.
     *
     * @return list<PurchasedLine>
     */
    public function linesForCustomer(int $customerId, int $limit = 100): array;

    /**
     * Compras del cliente restringidas a un rubro. Para "el rotor que compro
     * siempre".
     *
     * @return list<PurchasedLine>
     */
    public function linesForCustomerInCategory(int $customerId, int $categoryId, int $limit = 25): array;

    /** @return list<int> ids de producto que el cliente ya compró */
    public function purchasedProductIds(int $customerId): array;
}
