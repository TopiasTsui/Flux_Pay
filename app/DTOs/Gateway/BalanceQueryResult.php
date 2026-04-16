<?php

declare(strict_types=1);

namespace App\DTOs\Gateway;

final readonly class BalanceQueryResult
{
    public function __construct(
        public bool $success,
        public ?string $availableBalance = null,
        public ?string $holdBalance = null,
        public array $rawData = [],
    ) {}
}
