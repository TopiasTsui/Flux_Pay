<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Contracts\Repositories\MerchantRepositoryInterface;
use App\Contracts\Repositories\MerchantWalletRecordRepositoryInterface;
use App\Enums\WalletOperationType;
use App\Exceptions\InsufficientBalanceException;
use App\Helpers\MoneyHelper;
use App\Helpers\OrderNumberGenerator;
use Illuminate\Support\Facades\DB;

class MerchantWalletService
{
    public function __construct(
        private readonly MerchantRepositoryInterface $merchantRepo,
        private readonly MerchantWalletRecordRepositoryInterface $recordRepo,
    ) {}

    public function credit(
        int $merchantId,
        string $amount,
        WalletOperationType $type,
        ?string $systemOrderNo = null,
        ?string $remark = null,
    ): void {
        DB::transaction(function () use ($merchantId, $amount, $type, $systemOrderNo, $remark) {
            $merchant = $this->merchantRepo->lockForUpdate($merchantId);

            $preAvailable = $merchant->getAvailableBalance();
            $preHold = $merchant->getHoldBalance();
            $preTotal = $merchant->getTotalBalance();

            $newAvailable = MoneyHelper::add($preAvailable, $amount);
            $newTotal = MoneyHelper::add($preTotal, $amount);

            $this->merchantRepo->update($merchantId, [
                'available_balance' => $newAvailable,
                'total_balance' => $newTotal,
            ]);

            $this->recordRepo->create([
                'merchant_id' => $merchantId,
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

    public function freeze(
        int $merchantId,
        string $amount,
        ?string $systemOrderNo = null,
        ?string $remark = null,
    ): void {
        DB::transaction(function () use ($merchantId, $amount, $systemOrderNo, $remark) {
            $merchant = $this->merchantRepo->lockForUpdate($merchantId);

            $preAvailable = $merchant->getAvailableBalance();
            $preHold = $merchant->getHoldBalance();
            $preTotal = $merchant->getTotalBalance();

            if (! MoneyHelper::gte($preAvailable, $amount)) {
                throw new InsufficientBalanceException('merchant', $merchantId, $amount, $preAvailable);
            }

            $newAvailable = MoneyHelper::sub($preAvailable, $amount);
            $newHold = MoneyHelper::add($preHold, $amount);

            $this->merchantRepo->update($merchantId, [
                'available_balance' => $newAvailable,
                'hold_balance' => $newHold,
            ]);

            $this->recordRepo->create([
                'merchant_id' => $merchantId,
                'sn' => OrderNumberGenerator::walletSn(),
                'type_code' => WalletOperationType::FREEZE,
                'amount' => $amount,
                'pre_total_balance' => $preTotal,
                'pre_available_balance' => $preAvailable,
                'pre_hold_balance' => $preHold,
                'total_balance' => $preTotal,
                'available_balance' => $newAvailable,
                'hold_balance' => $newHold,
                'system_order_no' => $systemOrderNo,
                'remark' => $remark,
                'created_at' => now(),
            ]);
        });
    }

    public function unfreeze(
        int $merchantId,
        string $amount,
        ?string $systemOrderNo = null,
        ?string $remark = null,
    ): void {
        DB::transaction(function () use ($merchantId, $amount, $systemOrderNo, $remark) {
            $merchant = $this->merchantRepo->lockForUpdate($merchantId);

            $preAvailable = $merchant->getAvailableBalance();
            $preHold = $merchant->getHoldBalance();
            $preTotal = $merchant->getTotalBalance();

            if (! MoneyHelper::gte($preHold, $amount)) {
                throw new InsufficientBalanceException('merchant', $merchantId, $amount, $preHold);
            }

            $newAvailable = MoneyHelper::add($preAvailable, $amount);
            $newHold = MoneyHelper::sub($preHold, $amount);

            $this->merchantRepo->update($merchantId, [
                'available_balance' => $newAvailable,
                'hold_balance' => $newHold,
            ]);

            $this->recordRepo->create([
                'merchant_id' => $merchantId,
                'sn' => OrderNumberGenerator::walletSn(),
                'type_code' => WalletOperationType::UNFREEZE,
                'amount' => $amount,
                'pre_total_balance' => $preTotal,
                'pre_available_balance' => $preAvailable,
                'pre_hold_balance' => $preHold,
                'total_balance' => $preTotal,
                'available_balance' => $newAvailable,
                'hold_balance' => $newHold,
                'system_order_no' => $systemOrderNo,
                'remark' => $remark,
                'created_at' => now(),
            ]);
        });
    }

    public function debit(
        int $merchantId,
        string $amount,
        WalletOperationType $type,
        ?string $systemOrderNo = null,
        ?string $remark = null,
    ): void {
        DB::transaction(function () use ($merchantId, $amount, $type, $systemOrderNo, $remark) {
            $merchant = $this->merchantRepo->lockForUpdate($merchantId);

            $preAvailable = $merchant->getAvailableBalance();
            $preHold = $merchant->getHoldBalance();
            $preTotal = $merchant->getTotalBalance();

            if (! MoneyHelper::gte($preAvailable, $amount)) {
                throw new InsufficientBalanceException('merchant', $merchantId, $amount, $preAvailable);
            }

            $newAvailable = MoneyHelper::sub($preAvailable, $amount);
            $newTotal = MoneyHelper::sub($preTotal, $amount);

            $this->merchantRepo->update($merchantId, [
                'available_balance' => $newAvailable,
                'total_balance' => $newTotal,
            ]);

            $this->recordRepo->create([
                'merchant_id' => $merchantId,
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

    /**
     * Deduct from hold_balance permanently (e.g. after a successful withdraw settlement).
     * Total balance also decreases since the funds are leaving the system.
     */
    public function settleFreeze(
        int $merchantId,
        string $amount,
        WalletOperationType $type,
        ?string $systemOrderNo = null,
        ?string $remark = null,
    ): void {
        DB::transaction(function () use ($merchantId, $amount, $type, $systemOrderNo, $remark) {
            $merchant = $this->merchantRepo->lockForUpdate($merchantId);

            $preAvailable = $merchant->getAvailableBalance();
            $preHold = $merchant->getHoldBalance();
            $preTotal = $merchant->getTotalBalance();

            if (! MoneyHelper::gte($preHold, $amount)) {
                throw new InsufficientBalanceException('merchant', $merchantId, $amount, $preHold);
            }

            $newHold = MoneyHelper::sub($preHold, $amount);
            $newTotal = MoneyHelper::sub($preTotal, $amount);

            $this->merchantRepo->update($merchantId, [
                'hold_balance' => $newHold,
                'total_balance' => $newTotal,
            ]);

            $this->recordRepo->create([
                'merchant_id' => $merchantId,
                'sn' => OrderNumberGenerator::walletSn(),
                'type_code' => $type,
                'amount' => $amount,
                'pre_total_balance' => $preTotal,
                'pre_available_balance' => $preAvailable,
                'pre_hold_balance' => $preHold,
                'total_balance' => $newTotal,
                'available_balance' => $preAvailable,
                'hold_balance' => $newHold,
                'system_order_no' => $systemOrderNo,
                'remark' => $remark,
                'created_at' => now(),
            ]);
        });
    }
}
