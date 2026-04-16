<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\WithdrawOrderRepositoryInterface;
use App\Models\WithdrawOrder;

class WithdrawOrderRepository implements WithdrawOrderRepositoryInterface
{
    public function find(int $id): ?WithdrawOrder
    {
        return WithdrawOrder::find($id);
    }

    public function findOrFail(int $id): WithdrawOrder
    {
        return WithdrawOrder::findOrFail($id);
    }

    public function create(array $data): WithdrawOrder
    {
        return WithdrawOrder::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return (bool) WithdrawOrder::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return (bool) WithdrawOrder::where('id', $id)->delete();
    }

    public function findBySystemOrderNo(string $no): ?WithdrawOrder
    {
        return WithdrawOrder::where('system_order_no', $no)->first();
    }

    public function findByMerchantOrder(int $merchantId, string $merchantOrderNo): ?WithdrawOrder
    {
        return WithdrawOrder::where('merchant_id', $merchantId)
            ->where('merchant_order_no', $merchantOrderNo)
            ->first();
    }

    public function findByProviderOrder(int $pptId, string $providerOrderNo): ?WithdrawOrder
    {
        return WithdrawOrder::where('provider_payment_type_id', $pptId)
            ->where('provider_order_no', $providerOrderNo)
            ->first();
    }
}
