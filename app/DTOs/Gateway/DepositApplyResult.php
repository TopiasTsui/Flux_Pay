<?php

declare(strict_types=1);

namespace App\DTOs\Gateway;

final readonly class DepositApplyResult
{
    public function __construct(
        public bool $success,
        public ?string $providerOrderNo = null,
        public ?string $payUrl = null,
        public ?string $qrContent = null,
        public array $rawData = [],
    ) {}
}
