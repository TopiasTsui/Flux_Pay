<?php

declare(strict_types=1);

namespace Tests\Stubs;

use App\Contracts\Gateway\PaymentGatewayInterface;
use App\DTOs\Gateway\BalanceQueryResult;
use App\DTOs\Gateway\DepositApplyResult;
use App\DTOs\Gateway\DepositCallbackResult;
use App\DTOs\Gateway\WithdrawApplyResult;
use App\DTOs\Gateway\WithdrawCallbackResult;
use App\Enums\OrderStatus;

class FakeGateway implements PaymentGatewayInterface
{
    public ?DepositApplyResult $depositApplyResult = null;

    public ?DepositCallbackResult $depositCallbackResult = null;

    public ?WithdrawApplyResult $withdrawApplyResult = null;

    public ?WithdrawCallbackResult $withdrawCallbackResult = null;

    public ?BalanceQueryResult $balanceQueryResult = null;

    public array $lastDepositApplyData = [];

    public array $lastWithdrawApplyData = [];

    public function depositApply(array $data): DepositApplyResult
    {
        $this->lastDepositApplyData = $data;

        return $this->depositApplyResult ?? new DepositApplyResult(
            success: true,
            providerOrderNo: 'FAKE_' . ($data['system_order_no'] ?? 'unknown'),
            payUrl: 'https://fake-gateway.test/pay/123',
        );
    }

    public function depositQuery(array $data): DepositCallbackResult
    {
        return $this->depositCallbackResult ?? new DepositCallbackResult(
            success: true,
            systemOrderNo: $data['system_order_no'] ?? null,
            status: OrderStatus::SUCCESS,
        );
    }

    public function depositCallback(array $data, array $options = []): DepositCallbackResult
    {
        return $this->depositCallbackResult ?? new DepositCallbackResult(
            success: true,
            systemOrderNo: $data['system_order_no'] ?? null,
            status: OrderStatus::SUCCESS,
        );
    }

    public function withdrawApply(array $data): WithdrawApplyResult
    {
        $this->lastWithdrawApplyData = $data;

        return $this->withdrawApplyResult ?? new WithdrawApplyResult(
            success: true,
            providerOrderNo: 'FAKE_W_' . ($data['system_order_no'] ?? 'unknown'),
        );
    }

    public function withdrawQuery(array $data): WithdrawCallbackResult
    {
        return $this->withdrawCallbackResult ?? new WithdrawCallbackResult(
            success: true,
            systemOrderNo: $data['system_order_no'] ?? null,
            status: OrderStatus::SUCCESS,
        );
    }

    public function withdrawCallback(array $data, array $options = []): WithdrawCallbackResult
    {
        return $this->withdrawCallbackResult ?? new WithdrawCallbackResult(
            success: true,
            systemOrderNo: $data['system_order_no'] ?? null,
            status: OrderStatus::SUCCESS,
        );
    }

    public function balanceQuery(): BalanceQueryResult
    {
        return $this->balanceQueryResult ?? new BalanceQueryResult(
            success: true,
            availableBalance: '100000.000000',
            holdBalance: '0.000000',
        );
    }

    public function supportsDeposit(): bool
    {
        return true;
    }

    public function supportsWithdraw(): bool
    {
        return true;
    }
}
