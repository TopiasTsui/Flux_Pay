<?php

namespace App\Services\Gateway\Vendors;

use App\DTOs\Gateway\BalanceQueryResult;
use App\DTOs\Gateway\DepositApplyResult;
use App\DTOs\Gateway\DepositCallbackResult;
use App\DTOs\Gateway\WithdrawApplyResult;
use App\DTOs\Gateway\WithdrawCallbackResult;
use App\Enums\OrderStatus;
use App\Services\Gateway\AbstractPaymentGateway;

/**
 * Test/mock payment gateway for development and testing.
 */
class TestpayGateway extends AbstractPaymentGateway
{
    public function depositApply(array $data): DepositApplyResult
    {
        $this->logInfo('depositApply', 'Creating test deposit', $data);

        $providerOrderNo = 'TP' . now()->format('YmdHis') . mt_rand(1000, 9999);
        $payUrl = url("/pay/testpay?order={$providerOrderNo}");

        return new DepositApplyResult(
            success: true,
            providerOrderNo: $providerOrderNo,
            payUrl: $payUrl,
            qrContent: null,
            rawData: ['provider_order_no' => $providerOrderNo],
        );
    }

    public function depositQuery(array $data): DepositCallbackResult
    {
        $this->logInfo('depositQuery', 'Querying test deposit', $data);

        return new DepositCallbackResult(
            success: true,
            systemOrderNo: $data['system_order_no'] ?? '',
            providerOrderNo: $data['provider_order_no'] ?? '',
            status: OrderStatus::SUCCESS,
            actualAmount: $data['order_amount'] ?? '0',
            rawData: [],
        );
    }

    public function depositCallback(array $data, array $options = []): DepositCallbackResult
    {
        $this->logInfo('depositCallback', 'Processing test deposit callback', $data);

        $status = ($data['status'] ?? 'success') === 'success'
            ? OrderStatus::SUCCESS
            : OrderStatus::FAILED;

        return new DepositCallbackResult(
            success: true,
            systemOrderNo: $data['system_order_no'] ?? '',
            providerOrderNo: $data['provider_order_no'] ?? '',
            status: $status,
            actualAmount: $data['amount'] ?? '0',
            rawData: $data,
        );
    }

    public function withdrawApply(array $data): WithdrawApplyResult
    {
        $this->logInfo('withdrawApply', 'Creating test withdraw', $data);

        $providerOrderNo = 'TPW' . now()->format('YmdHis') . mt_rand(1000, 9999);

        return new WithdrawApplyResult(
            success: true,
            providerOrderNo: $providerOrderNo,
            rawData: ['provider_order_no' => $providerOrderNo],
        );
    }

    public function withdrawQuery(array $data): WithdrawCallbackResult
    {
        $this->logInfo('withdrawQuery', 'Querying test withdraw', $data);

        return new WithdrawCallbackResult(
            success: true,
            systemOrderNo: $data['system_order_no'] ?? '',
            providerOrderNo: $data['provider_order_no'] ?? '',
            status: OrderStatus::SUCCESS,
            rawData: [],
        );
    }

    public function withdrawCallback(array $data, array $options = []): WithdrawCallbackResult
    {
        $this->logInfo('withdrawCallback', 'Processing test withdraw callback', $data);

        $status = ($data['status'] ?? 'success') === 'success'
            ? OrderStatus::SUCCESS
            : OrderStatus::FAILED;

        return new WithdrawCallbackResult(
            success: true,
            systemOrderNo: $data['system_order_no'] ?? '',
            providerOrderNo: $data['provider_order_no'] ?? '',
            status: $status,
            rawData: $data,
        );
    }

    public function balanceQuery(): BalanceQueryResult
    {
        return new BalanceQueryResult(
            success: true,
            availableBalance: '999999.000000',
            holdBalance: '0.000000',
            rawData: [],
        );
    }
}
