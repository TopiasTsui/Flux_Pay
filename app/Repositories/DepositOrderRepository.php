<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\DepositOrderRepositoryInterface;
use App\Models\DepositOrder;

class DepositOrderRepository implements DepositOrderRepositoryInterface
{
    public function find(int $id): ?DepositOrder
    {
        return DepositOrder::find($id);
    }

    public function findOrFail(int $id): DepositOrder
    {
        return DepositOrder::findOrFail($id);
    }

    public function create(array $data): DepositOrder
    {
        return DepositOrder::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return (bool) DepositOrder::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return (bool) DepositOrder::where('id', $id)->delete();
    }

    public function findBySystemOrderNo(string $no): ?DepositOrder
    {
        return DepositOrder::where('system_order_no', $no)->first();
    }

    public function findByMerchantOrder(int $merchantId, string $merchantOrderNo): ?DepositOrder
    {
        return DepositOrder::where('merchant_id', $merchantId)
            ->where('merchant_order_no', $merchantOrderNo)
            ->first();
    }

    public function findByProviderOrder(int $pptId, string $providerOrderNo): ?DepositOrder
    {
        return DepositOrder::where('provider_payment_type_id', $pptId)
            ->where('provider_order_no', $providerOrderNo)
            ->first();
    }
}
