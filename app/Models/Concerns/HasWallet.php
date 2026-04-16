<?php

namespace App\Models\Concerns;

trait HasWallet
{
    public function getAvailableBalance(): string
    {
        return (string) $this->available_balance;
    }

    public function getHoldBalance(): string
    {
        return (string) $this->hold_balance;
    }

    public function getTotalBalance(): string
    {
        return (string) $this->total_balance;
    }

    public function hasEnoughBalance(string $amount): bool
    {
        return bccomp($this->available_balance, $amount, 6) >= 0;
    }
}
