<?php

declare(strict_types=1);

namespace App\Services\Merchant;

use App\Helpers\SignatureHelper;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MerchantNotificationService
{
    public function notify(string $url, array $data, string $md5key): bool
    {
        try {
            $data['sign'] = SignatureHelper::generate($data, $md5key);

            $response = Http::timeout(10)
                ->connectTimeout(5)
                ->post($url, $data);

            if (! $response->successful()) {
                Log::warning('Merchant notification failed: HTTP ' . $response->status(), [
                    'url' => $url,
                    'status' => $response->status(),
                ]);
                return false;
            }

            $body = strtolower(trim($response->body()));

            return str_contains($body, 'success') || str_contains($body, 'ok');
        } catch (\Throwable $e) {
            Log::error('Merchant notification exception', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
