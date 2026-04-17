<?php

namespace App\Enums;

enum AgentType: string
{
    case MERCHANT = 'merchant';
    case PROVIDER = 'provider';

    public function label(): string
    {
        return match ($this) {
            self::MERCHANT => 'Merchant Agent',
            self::PROVIDER => 'Provider Agent',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(
            fn (self $c) => [$c->value => $c->label()]
        )->all();
    }
}
