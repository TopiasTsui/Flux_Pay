<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Contracts\Repositories\ProviderRepositoryInterface;
use App\Contracts\Repositories\ProviderWalletRecordRepositoryInterface;
use App\Enums\WalletOperationType;
use App\Exceptions\InsufficientBalanceException;
use App\Helpers\MoneyHelper;
use App\Helpers\OrderNumberGenerator;
use Illuminate\Support\Facades\DB;

class ProviderWalletService
{
    public function __construct(
        private readonly ProviderRepositoryInterface $providerRepo,
        private readonly ProviderWalletRecordRepositoryInterface $recordRepo,
    ) {}

    public function credit(
        int $providerId,
        string $amount,
        WalletOperationType $type,
        ?string $systemOrderNo = null,
        ?string $remark = null,
    ): void {
        DB::transaction(function () use ($providerId, $amount, $type, $systemOrderNo, $remark) {
            $provider = $this->providerRepo->lockForUpdate($providerId);

            $preAvailable = $provider->getAvailableBalance();
            $preHold = $provider->getHoldBalance();
            $preTotal = $provider->getTotalBalance();

            $newAvailable = MoneyHelper::add($preAvailable, $amount);
            $newTotal = MoneyHelper::add($preTotal, $amount);

            $this->providerRepo->update($providerId, [
                'available_balance' => $newAvailable,
                'total_balance' => $newTotal,
            ]);

            $this->recordRepo->create([
                'provider_id' => $providerId,
                'sn' => OrderNumberGenerator::walletSn(),
                'type_code' => $type,
                'amount' => $amount,
                'pre_total_balance' => $preTotal,
                'pre_available_balance' => $preAvailable,
                'pre_hold_balance' => $preHold,
                'total_balance' => $newTotal,
                'available_balance' => $newAvailable,
                'hold_balance' => $preHold,
                'system_order_no' => $systemOrderNo,
                'remark' => $remark,
                'created_at' => now(),
            ]);
        });
    }

    public function debit(
        int $providerId,
        string $amount,
        WalletOperationType $type,
        ?string $systemOrderNo = null,
        ?string $remark = null,
    ): void {
        DB::transaction(function () use ($providerId, $amount, $type, $systemOrderNo, $remark) {
            $provider = $this->providerRepo->lockForUpdate($providerId);

            $preAvailable = $provider->getAvailableBalance();
            $preHold = $provider->getHoldBalance();
            $preTotal = $provider->getTotalBalance();

            if (! MoneyHelper::gte($preAvailable, $amount)) {
                throw new InsufficientBalanceException('provider', $providerId, $amount, $preAvailable);
            }

            $newAvailable = MoneyHelper::sub($preAvailable, $amount);
            $newTotal = MoneyHelper::sub($preTotal, $amount);

            $this->providerRepo->update($providerId, [
                'available_balance' => $newAvailable,
                'total_balance' => $newTotal,
            ]);

            $this->recordRepo->create([
                'provider_id' => $providerId,
                'sn' => OrderNumberGenerator::walletSn(),
                'type_code' => $type,
                'amount' => $amount,
                'pre_total_balance' => $preTotal,
                'pre_available_balance' => $preAvailable,
                'pre_hold_balance' => $preHold,
                'total_balance' => $newTotal,
                'available_balance' => $newAvailable,
                'hold_balance' => $preHold,
                'system_order_no' => $systemOrderNo,
                'remark' => $remark,
                'created_at' => now(),
            ]);
        });
    }
}
