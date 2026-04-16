<?php

declare(strict_types=1);

namespace App\DTOs\Gateway;

final readonly class WithdrawApplyResult
{
    public function __construct(
        public bool $success,
        public ?string $providerOrderNo = null,
        public array $rawData = [],
    ) {}
}
