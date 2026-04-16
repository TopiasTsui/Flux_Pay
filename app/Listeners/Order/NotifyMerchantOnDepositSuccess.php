<?php

declare(strict_types=1);

namespace App\Listeners\Order;

use App\Events\Order\DepositFundSettled;
use App\Jobs\MerchantNotificationJob;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyMerchantOnDepositSuccess implements ShouldQueue
{
    public function handle(DepositFundSettled $event): void
    {
        $order = $event->order;
        $merchant = $order->merchant;

        if (! $order->merchant_notify_url) {
            return;
        }

        $data = [
            'merchantNo' => $merchant->code,
            'orderNo' => $order->merchant_order_no,
            'systemOrderNo' => $order->system_order_no,
            'amount' => (string) $order->actual_amount,
            'status' => $order->status->name,
            'currency' => $order->currency,
        ];

        MerchantNotificationJob::dispatch(
            $order->merchant_notify_url,
            $data,
            $merchant->md5key,
            'deposit',
            $order->id,
        );
    }
}
