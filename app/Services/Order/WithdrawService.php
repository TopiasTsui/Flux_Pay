<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\Contracts\Repositories\MerchantRepositoryInterface;
use App\Contracts\Repositories\WithdrawOrderRepositoryInterface;
use App\DTOs\Gateway\WithdrawCallbackResult;
use App\Enums\CallbackStatus;
use App\Enums\FeeType;
use App\Enums\FundStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentDirection;
use App\Enums\WalletOperationType;
use App\Helpers\MoneyHelper;
use App\Helpers\OrderNumberGenerator;
use App\Models\Merchant;
use App\Models\WithdrawOrder;
use App\Services\Agent\CommissionCalculator;
use App\Services\Gateway\PaymentGatewayFactory;
use App\Services\Provider\ChannelSelector;
use App\Services\Wallet\AgentWalletService;
use App\Services\Wallet\MerchantWalletService;
use App\Services\Wallet\ProviderWalletService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WithdrawService
{
    public function __construct(
        private readonly WithdrawOrderRepositoryInterface $orderRepo,
        private readonly MerchantRepositoryInterface $merchantRepo,
        private readonly MerchantWalletService $merchantWallet,
        private readonly AgentWalletService $agentWallet,
        private readonly ProviderWalletService $providerWallet,
        private readonly CommissionCalculator $commissionCalc,
        private readonly ChannelSelector $channelSelector,
        private readonly PaymentGatewayFactory $gatewayFactory,
    ) {}

    public function apply(Merchant $merchant, array $data): WithdrawOrder
    {
        $existing = WithdrawOrder::where('merchant_id', $merchant->id)
            ->where('merchant_order_no', $data['merchant_order_no'])
            ->first();
        if ($existing) {
            return $existing;
        }

        $amount = (string) $data['amount'];
        $paymentTypeCode = $data['payment_type_code'] ?? null;

        $channel = $this->channelSelector->select(
            $merchant->id,
            PaymentDirection::WITHDRAW,
            $amount,
            $paymentTypeCode,
        );

        // Calculate merchant fee
        $mpt = $merchant->merchantPaymentTypes()
            ->where('payment_type_id', $channel->payment_type_id)
            ->first();

        $merchantFee = '0';
        if ($mpt && $mpt->withdraw_fee_type) {
            $merchantFee = FeeType::from($mpt->withdraw_fee_type)->calculate($amount, (string) $mpt->withdraw_fee);
        }

        // Calculate provider fee
        $providerFee = '0';
        if ($channel->withdraw_fee_type) {
            $providerFee = FeeType::from($channel->withdraw_fee_type)->calculate($amount, (string) $channel->withdraw_fee);
        }

        // Calculate agent commissions
        $agentResult = $mpt
            ? $this->commissionCalc->calculate($mpt, PaymentDirection::WITHDRAW, $amount)
            : null;

        $systemOrderNo = OrderNumberGenerator::generate('W');

        // Total debit = amount + merchant_fee
        $totalDebit = MoneyHelper::add($amount, $merchantFee);

        try {
            $order = DB::transaction(function () use (
                $merchant, $data, $amount, $channel, $merchantFee, $providerFee,
                $agentResult, $systemOrderNo, $totalDebit,
            ) {
                // Freeze the total debit amount from merchant wallet
                $this->merchantWallet->freeze(
                    $merchant->id,
                    $totalDebit,
                    $systemOrderNo,
                    "Withdraw freeze: {$systemOrderNo}",
                );

                return $this->orderRepo->create([
                    'merchant_id' => $merchant->id,
                    'merchant_order_no' => $data['merchant_order_no'],
                    'system_order_no' => $systemOrderNo,
                    'provider_payment_type_id' => $channel->id,
                    'order_amount' => $amount,
                    'actual_amount' => $amount,
                    'merchant_fee' => $merchantFee,
                    'provider_fee' => $providerFee,
                    'agent_fee' => $agentResult?->total ?? '0',
                    'agent_fee_map' => $agentResult?->agentFeeMap ?? [],
                    'provider_agent_fee' => '0',
                    'provider_agent_fee_map' => [],
                    'total_debit' => $totalDebit,
                    'currency' => $merchant->currency_code,
                    'status' => OrderStatus::PENDING->value,
                    'callback_status' => CallbackStatus::PENDING->value,
                    'fund_status' => FundStatus::PENDING->value,
                    'merchant_notify_url' => $data['notify_url'] ?? null,
                    'merchant_extra' => $data['extend'] ?? null,
                    'bank_code' => $data['bank_code'] ?? null,
                    'bank_account_name' => $data['bank_account_name'] ?? null,
                    'bank_account_no' => $data['bank_account_no'] ?? null,
                    'bank_branch' => $data['bank_branch'] ?? null,
                    'remark' => $data['remark'] ?? null,
                ]);
            });
        } catch (\Illuminate\Database\UniqueConstraintViolationException $e) {
            // Concurrent retry: the unique constraint won the race, transaction rolled back
            // (including the freeze). Return the row that already exists.
            $winner = WithdrawOrder::where('merchant_id', $merchant->id)
                ->where('merchant_order_no', $data['merchant_order_no'])
                ->first();
            if ($winner) {
                return $winner;
            }
            throw $e;
        }

        // Call payment gateway
        $gateway = $this->gatewayFactory->createFromProvider($channel->provider);
        $gatewayResult = $gateway->withdrawApply([
            'system_order_no' => $systemOrderNo,
            'amount' => $amount,
            'bank_code' => $data['bank_code'] ?? null,
            'bank_account_name' => $data['bank_account_name'] ?? null,
            'bank_account_no' => $data['bank_account_no'] ?? null,
            'bank_branch' => $data['bank_branch'] ?? null,
            'notify_url' => route('callback.withdraw', ['vendor' => $channel->provider->vendor_id]),
        ]);

        $updateData = [
            'provider_apply_time' => now(),
        ];

        if ($gatewayResult->success) {
            $updateData['status'] = OrderStatus::SENT_TO_PROVIDER->value;
            if ($gatewayResult->providerOrderNo) {
                $updateData['provider_order_no'] = $gatewayResult->providerOrderNo;
            }
        } else {
            // Unfreeze on gateway failure
            $this->merchantWallet->unfreeze(
                $merchant->id,
                $totalDebit,
                $systemOrderNo,
                "Withdraw gateway failed, unfreeze: {$systemOrderNo}",
            );
            $updateData['status'] = OrderStatus::FAILED->value;
        }

        $this->orderRepo->update($order->id, $updateData);
        $order = $this->orderRepo->findOrFail($order->id);

        return $order;
    }

    public function handleCallback(WithdrawOrder $order, WithdrawCallbackResult $result): void
    {
        if (OrderStatus::from($order->status)->isFinal()) {
            Log::info("Withdraw order #{$order->system_order_no} already in final state, skipping callback");
            return;
        }

        DB::transaction(function () use ($order, $result) {
            $updateData = [
                'callback_status' => CallbackStatus::PROVIDER_SUCCESS->value,
                'provider_callback_time' => now(),
            ];

            if ($result->providerOrderNo) {
                $updateData['provider_order_no'] = $result->providerOrderNo;
            }

            if ($result->status === OrderStatus::SUCCESS) {
                $updateData['status'] = OrderStatus::SUCCESS->value;
                $this->orderRepo->update($order->id, $updateData);

                $order = $this->orderRepo->findOrFail($order->id);
                $this->settleFunds($order);
            } elseif ($result->status === OrderStatus::FAILED) {
                $updateData['status'] = OrderStatus::FAILED->value;
                $this->orderRepo->update($order->id, $updateData);

                // Unfreeze on failure
                $order = $this->orderRepo->findOrFail($order->id);
                $this->merchantWallet->unfreeze(
                    $order->merchant_id,
                    (string) $order->total_debit,
                    $order->system_order_no,
                    "Withdraw failed, unfreeze: {$order->system_order_no}",
                );
            } else {
                $this->orderRepo->update($order->id, $updateData);
            }
        });
    }

    public function settleFunds(WithdrawOrder $order): void
    {
        if ($order->fund_status === FundStatus::SETTLED->value) {
            return;
        }

        DB::transaction(function () use ($order) {
            $systemOrderNo = $order->system_order_no;
            $totalDebit = (string) $order->total_debit;

            // Settle the frozen amount from merchant wallet
            $this->merchantWallet->settleFreeze(
                $order->merchant_id,
                $totalDebit,
                WalletOperationType::WITHDRAW_DEBIT,
                $systemOrderNo,
                "Withdraw settled: {$systemOrderNo}",
            );

            // Credit provider wallet (provider paid out on our behalf)
            $providerPaymentType = $order->providerPaymentType;
            if ($providerPaymentType) {
                $this->providerWallet->credit(
                    $providerPaymentType->provider_id,
                    (string) $order->actual_amount,
                    WalletOperationType::PROVIDER_WITHDRAW_CREDIT,
                    $systemOrderNo,
                    "Withdraw credit: {$systemOrderNo}",
                );
            }

            // Distribute agent commissions
            $agentFeeMap = $order->agent_fee_map ?? [];
            foreach ($agentFeeMap as $agentId => $commission) {
                if (MoneyHelper::isPositive((string) $commission)) {
                    $this->agentWallet->credit(
                        (int) $agentId,
                        (string) $commission,
                        WalletOperationType::COMMISSION_WITHDRAW,
                        $systemOrderNo,
                        "Withdraw commission: {$systemOrderNo}",
                    );
                }
            }

            $this->orderRepo->update($order->id, [
                'fund_status' => FundStatus::SETTLED->value,
                'fund_at' => now(),
            ]);
        });
    }
}
