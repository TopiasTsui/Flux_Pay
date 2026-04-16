<?php

declare(strict_types=1);

namespace App\Events\Order;

use App\DTOs\Gateway\DepositCallbackResult;
use App\Models\DepositOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DepositCallbackReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly DepositOrder $order,
        public readonly DepositCallbackResult $result,
    ) {}
}
