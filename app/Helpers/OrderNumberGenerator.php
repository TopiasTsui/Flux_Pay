<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class OrderNumberGenerator
{
    /**
     * Generate a unique system order number.
     * Format: FP + YmdHis + 6 random digits
     */
    public static function generate(string $prefix = 'FP'): string
    {
        return $prefix . now()->format('YmdHis') . mt_rand(100000, 999999);
    }

    /**
     * Generate wallet record serial number.
     */
    public static function walletSn(string $prefix = 'W'): string
    {
        return $prefix . now()->format('YmdHis') . Str::random(8);
    }
}
