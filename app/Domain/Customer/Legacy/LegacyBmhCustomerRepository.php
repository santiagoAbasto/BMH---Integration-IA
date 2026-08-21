<?php

declare(strict_types=1);

namespace App\Domain\Customer\Legacy;

use App\Domain\Customer\Contracts\CustomerRepositoryInterface;
use App\Domain\Customer\DTO\CustomerAccount;
use Illuminate\Support\Facades\DB;

/**
 * Cliente desde `users` de la base legacy.
 *
 * El SELECT es explícito y deja fuera `password`, `remember_token`, `dni`,
 * `direccion` y `celular`: si el hash nunca se carga en memoria, no hay forma
 * de que termine en un log o en un payload al proveedor de IA.
 */
final class LegacyBmhCustomerRepository implements CustomerRepositoryInterface
{
    public function find(int $id): ?CustomerAccount
    {
        $row = DB::connection('mysql_legacy')->table('users')
            ->select('id', 'codigo', 'name', 'username', 'habilitado', 'descuento', 'reventa', 'vendedor_id', 'rol')
            ->where('id', $id)
            ->first();

        if ($row === null) {
            return null;
        }

        return new CustomerAccount(
            id: (int) $row->id,
            code: $this->nullIfBlank($row->codigo),
            displayName: $this->nullIfBlank($row->name) ?? $this->nullIfBlank($row->username) ?? 'Cliente',
            enabled: (bool) $row->habilitado,
            discountPercent: (float) $row->descuento,
            resalePercent: $this->numericOrNull($row->reventa),
            sellerId: $row->vendedor_id === null ? null : (int) $row->vendedor_id,
        );
    }

    private function nullIfBlank(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function numericOrNull(mixed $value): ?float
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' || ! is_numeric($value) ? null : (float) $value;
    }
}
