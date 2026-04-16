<?php

declare(strict_types=1);

namespace App\Listeners\Order;

use App\Enums\OrderStatus;
use App\Events\Order\WithdrawCallbackReceived;
use App\Events\Order\WithdrawFundSettled;
use App\Services\Order\WithdrawService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class SettleWithdrawFunds implements ShouldQueue
{
    public function __construct(
        private readonly WithdrawService $withdrawService,
    ) {}

    public function handle(WithdrawCallbackReceived $event): void
    {
        if ($event->result->status !== OrderStatus::SUCCESS) {
            return;
        }

        try {
            $this->withdrawService->settleFunds($event->order);
            WithdrawFundSettled::dispatch($event->order->fresh());
        } catch (\Throwable $e) {
            Log::error('Failed to settle withdraw funds', [
                'order_id' => $event->order->id,
                'system_order_no' => $event->order->system_order_no,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
