<?php

namespace App\Services\Gateway;

use App\Contracts\Gateway\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

abstract class AbstractPaymentGateway implements PaymentGatewayInterface
{
    protected array $config = [];
    protected string $vendorId = '';

    public function setConfig(array $config): static
    {
        $this->config = $config;

        return $this;
    }

    public function setVendorId(string $vendorId): static
    {
        $this->vendorId = $vendorId;

        return $this;
    }

    public function supportsDeposit(): bool
    {
        return true;
    }

    public function supportsWithdraw(): bool
    {
        return true;
    }

    protected function getConfigValue(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    protected function makeHttpRequest(string $url, array $data, string $method = 'POST'): array
    {
        $timeout = (int) $this->getConfigValue('timeout', 30);
        $retry = (int) $this->getConfigValue('retry', 2);

        try {
            $request = Http::timeout($timeout)->retry($retry, 1000);

            $response = match (strtoupper($method)) {
                'GET' => $request->get($url, $data),
                default => $request->post($url, $data),
            };

            $result = $response->json() ?? [];

            $this->logInfo('makeHttpRequest', "Response from {$url}", [
                'status' => $response->status(),
                'body_length' => strlen($response->body()),
            ]);

            return $result;
        } catch (\Throwable $e) {
            $this->logError('makeHttpRequest', "Request to {$url} failed: {$e->getMessage()}");
            throw $e;
        }
    }

    protected function generateSignature(array $data): string
    {
        ksort($data);
        $str = collect($data)->map(fn ($v, $k) => "{$k}={$v}")->implode('&');

        return md5($str);
    }

    protected function logInfo(string $method, string $message, array $context = []): void
    {
        Log::channel($this->getLogChannel())->info("[{$this->vendorId}] {$method}: {$message}", $context);
    }

    protected function logError(string $method, string $message, array $context = []): void
    {
        Log::channel($this->getLogChannel())->error("[{$this->vendorId}] {$method}: {$message}", $context);
    }

    protected function getLogChannel(): string
    {
        return 'single';
    }
}
