<?php

declare(strict_types=1);

namespace App\Listeners\Order;

use App\Enums\FundStatus;
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
        try {
            // handleCallback is the single source of truth: it guards the final
            // state, advances order.status (SUCCESS/FAILED) and settles funds.
            $this->depositService->handleCallback($event->order, $event->result);
        } catch (\Throwable $e) {
            Log::error('Failed to process deposit callback', [
                'order_id' => $event->order->id,
                'system_order_no' => $event->order->system_order_no,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }

        $order = $event->order->fresh();

        if ($order
            && OrderStatus::from($order->status) === OrderStatus::SUCCESS
            && $order->fund_status === FundStatus::SETTLED->value) {
            DepositFundSettled::dispatch($order);
        }
    }
}
