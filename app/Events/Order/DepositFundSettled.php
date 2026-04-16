<?php

declare(strict_types=1);

namespace App\Events\Order;

use App\Models\DepositOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DepositFundSettled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly DepositOrder $order,
    ) {}
}
