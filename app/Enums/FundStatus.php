<?php

namespace App\Enums;

enum FundStatus: int
{
    case PENDING = 0;
    case SETTLED = 1;

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SETTLED => 'Settled',
        };
    }
}
