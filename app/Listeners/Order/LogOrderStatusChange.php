<?php

declare(strict_types=1);

namespace App\Listeners\Order;

use App\Events\Order\DepositCallbackReceived;
use App\Events\Order\WithdrawCallbackReceived;
use App\Models\OrderLog;
use Illuminate\Contracts\Queue\ShouldQueue;

class LogOrderStatusChange implements ShouldQueue
{
    public function handle(DepositCallbackReceived|WithdrawCallbackReceived $event): void
    {
        $order = $event->order;
        $result = $event->result;

        OrderLog::create([
            'orderable_type' => get_class($order),
            'orderable_id' => $order->id,
            'action' => 'provider_callback',
            'request_data' => $result->rawData,
            'response_data' => [
                'success' => $result->success,
                'status' => $result->status?->name,
                'provider_order_no' => $result->providerOrderNo,
            ],
            'ip_address' => request()->ip(),
            'remark' => 'Provider callback received',
            'created_at' => now(),
        ]);
    }
}
