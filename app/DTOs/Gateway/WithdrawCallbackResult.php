<?php

declare(strict_types=1);

namespace App\DTOs\Gateway;

use App\Enums\OrderStatus;

final readonly class WithdrawCallbackResult
{
    public function __construct(
        public bool $success,
        public ?string $systemOrderNo = null,
        public ?string $providerOrderNo = null,
        public ?OrderStatus $status = null,
        public array $rawData = [],
    ) {}
}
