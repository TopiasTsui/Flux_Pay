<?php

declare(strict_types=1);

namespace App\Services\Wallet;

use App\Contracts\Repositories\AgentRepositoryInterface;
use App\Contracts\Repositories\AgentWalletRecordRepositoryInterface;
use App\Enums\WalletOperationType;
use App\Exceptions\InsufficientBalanceException;
use App\Helpers\MoneyHelper;
use App\Helpers\OrderNumberGenerator;
use Illuminate\Support\Facades\DB;

class AgentWalletService
{
    public function __construct(
        private readonly AgentRepositoryInterface $agentRepo,
        private readonly AgentWalletRecordRepositoryInterface $recordRepo,
    ) {}

    public function credit(
        int $agentId,
        string $amount,
        WalletOperationType $type,
        ?string $systemOrderNo = null,
        ?string $remark = null,
    ): void {
        DB::transaction(function () use ($agentId, $amount, $type, $systemOrderNo, $remark) {
            $agent = $this->agentRepo->lockForUpdate($agentId);

            $preAvailable = $agent->getAvailableBalance();
            $preHold = $agent->getHoldBalance();
            $preTotal = $agent->getTotalBalance();

            $newAvailable = MoneyHelper::add($preAvailable, $amount);
            $newTotal = MoneyHelper::add($preTotal, $amount);

            $this->agentRepo->update($agentId, [
                'available_balance' => $newAvailable,
                'total_balance' => $newTotal,
            ]);

            $this->recordRepo->create([
                'agent_id' => $agentId,
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
        int $agentId,
        string $amount,
        WalletOperationType $type,
        ?string $systemOrderNo = null,
        ?string $remark = null,
    ): void {
        DB::transaction(function () use ($agentId, $amount, $type, $systemOrderNo, $remark) {
            $agent = $this->agentRepo->lockForUpdate($agentId);

            $preAvailable = $agent->getAvailableBalance();
            $preHold = $agent->getHoldBalance();
            $preTotal = $agent->getTotalBalance();

            if (! MoneyHelper::gte($preAvailable, $amount)) {
                throw new InsufficientBalanceException('agent', $agentId, $amount, $preAvailable);
            }

            $newAvailable = MoneyHelper::sub($preAvailable, $amount);
            $newTotal = MoneyHelper::sub($preTotal, $amount);

            $this->agentRepo->update($agentId, [
                'available_balance' => $newAvailable,
                'total_balance' => $newTotal,
            ]);

            $this->recordRepo->create([
                'agent_id' => $agentId,
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
