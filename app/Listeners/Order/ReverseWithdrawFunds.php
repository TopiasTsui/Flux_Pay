<?php

declare(strict_types=1);

namespace App\Listeners\Order;

use App\Enums\OrderStatus;
use App\Events\Order\WithdrawCallbackReceived;
use App\Events\Order\WithdrawFundReversed;
use App\Services\Order\WithdrawService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Log;

class ReverseWithdrawFunds implements ShouldQueue
{
    public function __construct(
        private readonly WithdrawService $withdrawService,
    ) {}

    public function handle(WithdrawCallbackReceived $event): void
    {
        if ($event->result->status !== OrderStatus::FAILED) {
            return;
        }

        try {
            $this->withdrawService->handleCallback($event->order, $event->result);
            WithdrawFundReversed::dispatch($event->order->fresh());
        } catch (\Throwable $e) {
            Log::error('Failed to reverse withdraw funds', [
                'order_id' => $event->order->id,
                'system_order_no' => $event->order->system_order_no,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
