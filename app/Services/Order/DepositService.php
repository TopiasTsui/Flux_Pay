<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Contracts\Repositories\DepositOrderRepositoryInterface;
use App\Contracts\Repositories\MerchantRepositoryInterface;
use App\DTOs\Gateway\DepositCallbackResult;
use App\Enums\CallbackStatus;
use App\Enums\FundStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentDirection;
use App\Enums\WalletOperationType;
use App\Helpers\MoneyHelper;
use App\Helpers\OrderNumberGenerator;
use App\Models\DepositOrder;
use App\Models\Merchant;
use App\Services\Agent\CommissionCalculator;
use App\Services\Gateway\PaymentGatewayFactory;
use App\Services\Provider\ChannelSelector;
use App\Services\Wallet\AgentWalletService;
use App\Services\Wallet\MerchantWalletService;
use App\Services\Wallet\ProviderWalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DepositService
{
    public function __construct(
        private readonly DepositOrderRepositoryInterface $orderRepo,
        private readonly MerchantRepositoryInterface $merchantRepo,
        private readonly MerchantWalletService $merchantWallet,
        private readonly AgentWalletService $agentWallet,
        private readonly ProviderWalletService $providerWallet,
        private readonly CommissionCalculator $commissionCalc,
        private readonly ChannelSelector $channelSelector,
        private readonly PaymentGatewayFactory $gatewayFactory,
    ) {}

    public function apply(Merchant $merchant, array $data): DepositOrder
    {
        $amount = (string) $data['amount'];
        $paymentTypeCode = $data['payment_type_code'] ?? null;

        $channel = $this->channelSelector->select(
            $merchant->id,
            PaymentDirection::DEPOSIT,
            $amount,
            $paymentTypeCode,
        );

        // Calculate merchant fee
        $mpt = $merchant->merchantPaymentTypes()
            ->where('payment_type_id', $channel->payment_type_id)
            ->first();

        $merchantFee = '0';
        if ($mpt && $mpt->deposit_fee_type) {
            $merchantFee = $mpt->deposit_fee_type->calculate($amount, (string) $mpt->deposit_fee);
        }

        // Calculate provider fee
        $providerFee = '0';
        if ($channel->deposit_fee_type) {
            $providerFee = $channel->deposit_fee_type->calculate($amount, (string) $channel->deposit_fee);
        }

        // Calculate agent commissions
        $agentResult = $mpt
            ? $this->commissionCalc->calculate($mpt, PaymentDirection::DEPOSIT, $amount)
            : null;

        $systemOrderNo = OrderNumberGenerator::generate('D');

        // Net amount credited to merchant = amount - merchant_fee
        $merchantBalanceChange = MoneyHelper::sub($amount, $merchantFee);

        $order = $this->orderRepo->create([
            'merchant_id' => $merchant->id,
            'merchant_order_no' => $data['merchant_order_no'],
            'system_order_no' => $systemOrderNo,
            'provider_payment_type_id' => $channel->id,
            'order_amount' => $amount,
            'actual_amount' => $amount,
            'merchant_balance_change' => $merchantBalanceChange,
            'merchant_fee' => $merchantFee,
            'provider_fee' => $providerFee,
            'agent_fee' => $agentResult?->total ?? '0',
            'agent_fee_map' => $agentResult?->agentFeeMap ?? [],
            'provider_agent_fee' => '0',
            'provider_agent_fee_map' => [],
            'currency' => $merchant->currency_code,
            'status' => OrderStatus::PENDING,
            'callback_status' => CallbackStatus::PENDING,
            'fund_status' => FundStatus::PENDING,
            'merchant_notify_url' => $data['notify_url'] ?? null,
            'merchant_extra' => $data['extend'] ?? null,
            'bank_code' => $data['bank_code'] ?? null,
            'payer_name' => $data['payer_name'] ?? null,
            'remark' => $data['remark'] ?? null,
        ]);

        // Call payment gateway
        $gateway = $this->gatewayFactory->createFromProvider($channel->provider);
        $gatewayResult = $gateway->depositApply([
            'system_order_no' => $systemOrderNo,
            'amount' => $amount,
            'bank_code' => $data['bank_code'] ?? null,
            'payer_name' => $data['payer_name'] ?? null,
            'notify_url' => route('callback.deposit', ['provider' => $channel->provider->provider_no]),
            'extra' => $data['extend'] ?? null,
        ]);

        $updateData = [
            'provider_apply_time' => now(),
        ];

        if ($gatewayResult->success) {
            $updateData['status'] = OrderStatus::SENT_TO_PROVIDER;
            if ($gatewayResult->providerOrderNo) {
                $updateData['provider_order_no'] = $gatewayResult->providerOrderNo;
            }
        } else {
            $updateData['status'] = OrderStatus::FAILED;
        }

        $this->orderRepo->update($order->id, $updateData);
        $order = $this->orderRepo->findOrFail($order->id);

        return $order;
    }

    public function handleCallback(DepositOrder $order, DepositCallbackResult $result): void
    {
        if ($order->status->isFinal()) {
            Log::info("Deposit order #{$order->system_order_no} already in final state, skipping callback");
            return;
        }

        DB::transaction(function () use ($order, $result) {
            $updateData = [
                'callback_status' => CallbackStatus::PROVIDER_SUCCESS,
                'provider_callback_time' => now(),
            ];

            if ($result->providerOrderNo) {
                $updateData['provider_order_no'] = $result->providerOrderNo;
            }

            if ($result->actualAmount) {
                $updateData['actual_amount'] = $result->actualAmount;
            }

            if ($result->status === OrderStatus::SUCCESS) {
                $updateData['status'] = OrderStatus::SUCCESS;
                $this->orderRepo->update($order->id, $updateData);

                $order = $this->orderRepo->findOrFail($order->id);
                $this->settleFunds($order);
            } elseif ($result->status === OrderStatus::FAILED) {
                $updateData['status'] = OrderStatus::FAILED;
                $this->orderRepo->update($order->id, $updateData);
            } else {
                $this->orderRepo->update($order->id, $updateData);
            }
        });
    }

    public function settleFunds(DepositOrder $order): void
    {
        if ($order->fund_status === FundStatus::SETTLED) {
            return;
        }

        DB::transaction(function () use ($order) {
            $systemOrderNo = $order->system_order_no;
            $actualAmount = (string) $order->actual_amount;

            // Recalculate merchant fee based on actual amount if it differs
            $merchantFee = (string) $order->merchant_fee;
            $merchantCredit = MoneyHelper::sub($actualAmount, $merchantFee);

            // Credit merchant wallet
            $this->merchantWallet->credit(
                $order->merchant_id,
                $merchantCredit,
                WalletOperationType::DEPOSIT_INCOME,
                $systemOrderNo,
                "Deposit income: {$systemOrderNo}",
            );

            // Debit provider wallet (provider owes us)
            $providerPaymentType = $order->providerPaymentType;
            if ($providerPaymentType) {
                $this->providerWallet->debit(
                    $providerPaymentType->provider_id,
                    $actualAmount,
                    WalletOperationType::PROVIDER_DEPOSIT_DEBIT,
                    $systemOrderNo,
                    "Deposit debit: {$systemOrderNo}",
                );
            }

            // Distribute agent commissions
            $agentFeeMap = $order->agent_fee_map ?? [];
            foreach ($agentFeeMap as $agentId => $commission) {
                if (MoneyHelper::isPositive((string) $commission)) {
                    $this->agentWallet->credit(
                        (int) $agentId,
                        (string) $commission,
                        WalletOperationType::COMMISSION_DEPOSIT,
                        $systemOrderNo,
                        "Deposit commission: {$systemOrderNo}",
                    );
                }
            }

            $this->orderRepo->update($order->id, [
                'fund_status' => FundStatus::SETTLED,
                'fund_at' => now(),
            ]);
        });
    }
}
