<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\DepositOrder;

interface DepositOrderRepositoryInterface
{
    public function find(int $id): ?DepositOrder;

    public function findOrFail(int $id): DepositOrder;

    public function create(array $data): DepositOrder;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function findBySystemOrderNo(string $no): ?DepositOrder;

    public function findByMerchantOrder(int $merchantId, string $merchantOrderNo): ?DepositOrder;

    public function findByProviderOrder(int $pptId, string $providerOrderNo): ?DepositOrder;
}
