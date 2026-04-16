<?php

declare(strict_types=1);

namespace App\Events\Order;

use App\DTOs\Gateway\WithdrawCallbackResult;
use App\Models\WithdrawOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WithdrawCallbackReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WithdrawOrder $order,
        public readonly WithdrawCallbackResult $result,
    ) {}
}
