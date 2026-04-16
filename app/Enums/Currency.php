<?php

namespace App\Enums;

enum Currency: string
{
    case PHP = 'PHP';
    case CNY = 'CNY';
    case USD = 'USD';
    case THB = 'THB';
    case VND = 'VND';
    case IDR = 'IDR';
    case MYR = 'MYR';
    case INR = 'INR';
    case BRL = 'BRL';

    public function label(): string
    {
        return match ($this) {
            self::PHP => 'Philippine Peso',
            self::CNY => 'Chinese Yuan',
            self::USD => 'US Dollar',
            self::THB => 'Thai Baht',
            self::VND => 'Vietnamese Dong',
            self::IDR => 'Indonesian Rupiah',
            self::MYR => 'Malaysian Ringgit',
            self::INR => 'Indian Rupee',
            self::BRL => 'Brazilian Real',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $case) => [$case->value => $case->label()]
        )->all();
    }
}
