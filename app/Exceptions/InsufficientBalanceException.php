<?php

namespace App\Exceptions;

class InsufficientBalanceException extends WalletException
{
    public function __construct(string $entityType, int $entityId, string $required, string $available)
    {
        parent::__construct(
            "Insufficient balance for {$entityType}#{$entityId}: required={$required}, available={$available}"
        );
    }
}
