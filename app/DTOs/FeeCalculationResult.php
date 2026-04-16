<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class FeeCalculationResult
{
    public function __construct(
        public string $merchantFee,
        public string $providerFee,
    ) {}
}
