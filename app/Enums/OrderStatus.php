<?php

namespace App\Enums;

enum OrderStatus: int
{
    case PENDING = 0;
    case SENT_TO_PROVIDER = 1;
    case FAILED = 3;
    case SUCCESS = 4;
    case CANCELLED = 5;

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SENT_TO_PROVIDER => 'Sent to Provider',
            self::FAILED => 'Failed',
            self::SUCCESS => 'Success',
            self::CANCELLED => 'Cancelled',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $case) => [$case->value => $case->label()]
        )->all();
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::FAILED, self::SUCCESS, self::CANCELLED]);
    }
}
