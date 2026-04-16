<?php

namespace App\Services\Gateway;

class GatewayResponse
{
    public function __construct(
        public readonly int $code,
        public readonly string $message,
        public readonly array $data = [],
    ) {}

    public function isSuccess(): bool
    {
        return $this->code === 0;
    }

    public static function success(array $data = [], string $message = 'Success'): static
    {
        return new static(0, $message, $data);
    }

    public static function fail(string $message, int $code = 1, array $data = []): static
    {
        return new static($code, $message, $data);
    }
}
