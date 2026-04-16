<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\WithdrawOrder;

interface WithdrawOrderRepositoryInterface
{
    public function find(int $id): ?WithdrawOrder;

    public function findOrFail(int $id): WithdrawOrder;

    public function create(array $data): WithdrawOrder;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    public function findBySystemOrderNo(string $no): ?WithdrawOrder;

    public function findByMerchantOrder(int $merchantId, string $merchantOrderNo): ?WithdrawOrder;

    public function findByProviderOrder(int $pptId, string $providerOrderNo): ?WithdrawOrder;
}
