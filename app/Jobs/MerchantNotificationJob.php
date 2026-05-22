<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\CallbackStatus;
use App\Models\DepositOrder;
use App\Models\WithdrawOrder;
use App\Services\Merchant\MerchantNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class MerchantNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $backoff = 60;

    public function __construct(
        private readonly string $url,
        private readonly array $data,
        private readonly string $md5key,
        private readonly string $orderType,
        private readonly int $orderId,
    ) {
        $this->onQueue('fluxpay-notification');
    }

    public function handle(MerchantNotificationService $service): void
    {
        $success = $service->notify($this->url, $this->data, $this->md5key);

        $order = $this->resolveOrder();

        if (! $order) {
            Log::error('MerchantNotificationJob: Order not found', [
                'order_type' => $this->orderType,
                'order_id' => $this->orderId,
            ]);

            return;
        }

        if ($success) {
            $order->update(['callback_status' => CallbackStatus::MERCHANT_SUCCESS->value]);
        } else {
            throw new \RuntimeException("Merchant notification failed for {$this->orderType} order #{$this->orderId}");
        }
    }

    public function failed(?\Throwable $exception): void
    {
        Log::error('MerchantNotificationJob failed permanently', [
            'order_type' => $this->orderType,
            'order_id' => $this->orderId,
            'url' => $this->url,
            'error' => $exception?->getMessage(),
        ]);

        $order = $this->resolveOrder();

        if ($order) {
            $order->update(['callback_status' => CallbackStatus::MERCHANT_FAILED->value]);
        }
    }

    private function resolveOrder(): DepositOrder|WithdrawOrder|null
    {
        return match ($this->orderType) {
            'deposit' => DepositOrder::find($this->orderId),
            'withdraw' => WithdrawOrder::find($this->orderId),
            default => null,
        };
    }
}
