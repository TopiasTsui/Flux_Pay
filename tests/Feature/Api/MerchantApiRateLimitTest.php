<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MerchantApiRateLimitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Clear any leftover counters across tests.
        RateLimiter::clear('merchant-api:no:M-RL-A');
        RateLimiter::clear('merchant-api:no:M-RL-B');
        RateLimiter::clear('merchant-api:ip:127.0.0.1');

        config(['fluxpay.merchant_api_rate_limit_per_minute' => 5]);
    }

    #[Test]
    public function requests_below_the_limit_pass_through(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $response = $this->postJson('/api/deposit/apply', ['merchantNo' => 'M-RL-A']);
            // Auth failures (missing signature etc.) are fine — we only care the throttle didn't fire.
            $this->assertNotSame(1007, $response->json('code'));
        }
    }

    #[Test]
    public function exceeding_the_limit_returns_429_and_code_1007(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/deposit/apply', ['merchantNo' => 'M-RL-A']);
        }

        $response = $this->postJson('/api/deposit/apply', ['merchantNo' => 'M-RL-A']);

        $response->assertStatus(429);
        $this->assertSame(1007, $response->json('code'));
        $this->assertNotEmpty($response->headers->get('Retry-After'));
    }

    #[Test]
    public function different_merchant_nos_have_independent_buckets(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/deposit/apply', ['merchantNo' => 'M-RL-A']);
        }

        // M-RL-A is now throttled, but M-RL-B should be free.
        $response = $this->postJson('/api/deposit/apply', ['merchantNo' => 'M-RL-B']);
        $this->assertNotSame(1007, $response->json('code'));
    }

    #[Test]
    public function requests_without_merchant_no_share_an_ip_bucket(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/deposit/apply', []);
        }

        $response = $this->postJson('/api/deposit/apply', []);
        $this->assertSame(1007, $response->json('code'));
    }
}
