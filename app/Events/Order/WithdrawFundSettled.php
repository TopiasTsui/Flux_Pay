<?php

declare(strict_types=1);

namespace App\Events\Order;

use App\Models\WithdrawOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WithdrawFundSettled
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly WithdrawOrder $order,
    ) {}
}
