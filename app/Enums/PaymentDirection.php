<?php

namespace App\Enums;

enum PaymentDirection: string
{
    case DEPOSIT = 'deposit';
    case WITHDRAW = 'withdraw';

    public function label(): string
    {
        return match ($this) {
            self::DEPOSIT => 'Deposit',
            self::WITHDRAW => 'Withdraw',
        };
    }
}
