<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Events\Order\DepositCallbackReceived;
use App\Events\Order\WithdrawCallbackReceived;
use App\Models\DepositOrder;
use App\Models\WithdrawOrder;
use App\Services\Gateway\PaymentGatewayFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class OrderQueryPollingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        private readonly string $orderType,
        private readonly int $orderId,
    ) {
        $this->onQueue('fluxpay-gateway');
    }

    public function handle(PaymentGatewayFactory $gatewayFactory): void
    {
        if ($this->orderType === 'deposit') {
            $this->handleDepositQuery($gatewayFactory);
        } elseif ($this->orderType === 'withdraw') {
            $this->handleWithdrawQuery($gatewayFactory);
        }
    }

    private function handleDepositQuery(PaymentGatewayFactory $gatewayFactory): void
    {
        $order = DepositOrder::find($this->orderId);

        if (! $order || $order->status->isFinal()) {
            return;
        }

        $providerPaymentType = $order->providerPaymentType;
        if (! $providerPaymentType) {
            Log::warning('OrderQueryPollingJob: No provider payment type for deposit order', [
                'order_id' => $this->orderId,
            ]);
            return;
        }

        $gateway = $gatewayFactory->createFromProvider($providerPaymentType->provider);

        $result = $gateway->depositQuery([
            'system_order_no' => $order->system_order_no,
            'provider_order_no' => $order->provider_order_no,
        ]);

        if ($result->status && $result->status->isFinal()) {
            DepositCallbackReceived::dispatch($order, $result);
        }
    }

    private function handleWithdrawQuery(PaymentGatewayFactory $gatewayFactory): void
    {
        $order = WithdrawOrder::find($this->orderId);

        if (! $order || $order->status->isFinal()) {
            return;
        }

        $providerPaymentType = $order->providerPaymentType;
        if (! $providerPaymentType) {
            Log::warning('OrderQueryPollingJob: No provider payment type for withdraw order', [
                'order_id' => $this->orderId,
            ]);
            return;
        }

        $gateway = $gatewayFactory->createFromProvider($providerPaymentType->provider);

        $result = $gateway->withdrawQuery([
            'system_order_no' => $order->system_order_no,
            'provider_order_no' => $order->provider_order_no,
        ]);

        if ($result->status && $result->status->isFinal()) {
            WithdrawCallbackReceived::dispatch($order, $result);
        }
    }
}
