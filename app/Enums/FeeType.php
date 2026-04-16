<?php

namespace App\Enums;

enum FeeType: int
{
    case PERCENTAGE = 1;
    case FIXED = 2;

    public function label(): string
    {
        return match ($this) {
            self::PERCENTAGE => 'Percentage',
            self::FIXED => 'Fixed',
        };
    }

    public function calculate(string $amount, string $rate): string
    {
        return match ($this) {
            self::PERCENTAGE => bcmul($amount, bcdiv($rate, '100', 8), 6),
            self::FIXED => $rate,
        };
    }
}
