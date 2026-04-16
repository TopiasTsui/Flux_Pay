<?php

namespace App\Enums;

enum CallbackStatus: int
{
    case PENDING = 0;
    case PROVIDER_SUCCESS = 1;
    case MERCHANT_SUCCESS = 2;
    case MERCHANT_FAILED = 3;

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PROVIDER_SUCCESS => 'Provider Callback OK',
            self::MERCHANT_SUCCESS => 'Merchant Notified',
            self::MERCHANT_FAILED => 'Merchant Notify Failed',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $case) => [$case->value => $case->label()]
        )->all();
    }
}
