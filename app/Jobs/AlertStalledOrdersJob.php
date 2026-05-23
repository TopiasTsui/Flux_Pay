<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\OrderStatus;
use App\Models\DepositOrder;
use App\Models\WithdrawOrder;
use App\Services\Alert\AlertDispatcher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class AlertStalledOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct()
    {
        $this->onQueue('fluxpay-notification');
    }

    public function handle(AlertDispatcher $dispatcher): void
    {
        $hours = (int) config('fluxpay.alert_stalled_threshold_hours', 4);
        $ttl = (int) config('fluxpay.alert_dedupe_ttl_seconds', 86400);
        $cutoff = Carbon::now()->subHours($hours);

        $this->scanAndAlert(DepositOrder::class, 'deposit', $cutoff, $ttl, $dispatcher);
        $this->scanAndAlert(WithdrawOrder::class, 'withdraw', $cutoff, $ttl, $dispatcher);
    }

    private function scanAndAlert(string $modelClass, string $type, Carbon $cutoff, int $ttl, AlertDispatcher $dispatcher): void
    {
        $modelClass::query()
            ->select(['id', 'system_order_no', 'merchant_id', 'provider_payment_type_id', 'order_amount', 'provider_apply_time'])
            ->where('status', OrderStatus::SENT_TO_PROVIDER->value)
            ->where('provider_apply_time', '<', $cutoff)
            ->orderBy('id')
            ->chunkById(100, function ($orders) use ($type, $ttl, $dispatcher) {
                foreach ($orders as $order) {
                    $key = "fluxpay:alert:stalled:{$type}:{$order->id}";
                    if (Cache::has($key)) {
                        continue;
                    }
                    Cache::put($key, 1, $ttl);

                    $dispatcher->dispatch(
                        "Stalled {$type} order",
                        "Order {$order->system_order_no} has been at SENT_TO_PROVIDER for more than the alert threshold.",
                        [
                            'order_id' => $order->id,
                            'system_order_no' => $order->system_order_no,
                            'merchant_id' => $order->merchant_id,
                            'amount' => $order->order_amount,
                            'provider_apply_time' => $order->provider_apply_time?->toDateTimeString(),
                        ],
                    );
                }
            });
    }
}
