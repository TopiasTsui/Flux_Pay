<?php

namespace App\Enums;

enum EntityStatus: int
{
    case INACTIVE = 0;
    case ACTIVE = 1;

    public function label(): string
    {
        return match ($this) {
            self::INACTIVE => 'Inactive',
            self::ACTIVE => 'Active',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $case) => [$case->value => $case->label()]
        )->all();
    }
}
