<?php

namespace App\Helpers;

class MoneyHelper
{
    private const SCALE = 6;

    public static function add(string $a, string $b): string
    {
        return bcadd($a, $b, self::SCALE);
    }

    public static function sub(string $a, string $b): string
    {
        return bcsub($a, $b, self::SCALE);
    }

    public static function mul(string $a, string $b): string
    {
        return bcmul($a, $b, self::SCALE);
    }

    public static function div(string $a, string $b): string
    {
        return bcdiv($a, $b, self::SCALE);
    }

    public static function gte(string $a, string $b): bool
    {
        return bccomp($a, $b, self::SCALE) >= 0;
    }

    public static function gt(string $a, string $b): bool
    {
        return bccomp($a, $b, self::SCALE) > 0;
    }

    public static function isPositive(string $amount): bool
    {
        return bccomp($amount, '0', self::SCALE) > 0;
    }

    public static function isZero(string $amount): bool
    {
        return bccomp($amount, '0', self::SCALE) === 0;
    }
}
