<?php

declare(strict_types=1);

namespace App\Listeners\Order;

use App\Enums\OrderStatus;
use App\Events\Order\DepositCallbackReceived;
use App\Events\Order\DepositFundSettled;
use App\Services\Order\DepositService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SettleDepositFunds implements ShouldQueue
{
    public function __construct(
        private readonly DepositService $depositService,
    ) {}

    public function handle(DepositCallbackReceived $event): void
    {
        if ($event->result->status !== OrderStatus::SUCCESS) {
            return;
        }

        try {
            $this->depositService->settleFunds($event->order);
            DepositFundSettled::dispatch($event->order->fresh());
        } catch (\Throwable $e) {
            Log::error('Failed to settle deposit funds', [
                'order_id' => $event->order->id,
                'system_order_no' => $event->order->system_order_no,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
