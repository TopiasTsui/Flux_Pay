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

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $c) => [$c->value => $c->label()]
        )->all();
    }
}
