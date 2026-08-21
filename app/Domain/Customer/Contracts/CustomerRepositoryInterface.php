<?php

declare(strict_types=1);

namespace App\Domain\Customer\Contracts;

use App\Domain\Customer\DTO\CustomerAccount;

interface CustomerRepositoryInterface
{
    /**
     * El cliente SIEMPRE se resuelve desde la sesión del backend, nunca desde
     * un id que mande el frontend.
     */
    public function find(int $id): ?CustomerAccount;
}
