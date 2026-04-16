<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Helpers\SignatureHelper;
use Database\Seeders\PaymentTypeSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BalanceQueryTest extends TestCase
{
    use RefreshDatabase;

    private string $md5key = 'test_secret_key_123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PaymentTypeSeeder::class);
        $this->seed(TestDataSeeder::class);
    }

    #[Test]
    public function balance_query_returns_correct_balances(): void
    {
        $params = [
            'merchantNo' => 'TEST001',
        ];

        $params['signature'] = SignatureHelper::generate($params, $this->md5key);

        $response = $this->postJson('/api/balance/query', $params);

        $response->assertStatus(200);
        $response->assertJsonPath('code', 0);
        $response->assertJsonPath('message', 'Success');
        $response->assertJsonStructure([
            'code',
            'message',
            'data' => [
                'merchantNo',
                'currency',
                'totalBalance',
                'availableBalance',
                'holdBalance',
            ],
            'timestamp',
        ]);
        $response->assertJsonPath('data.merchantNo', 'TEST001');
        $response->assertJsonPath('data.availableBalance', '100000.00');
        $response->assertJsonPath('data.holdBalance', '0.00');
    }

    #[Test]
    public function balance_query_with_invalid_signature_returns_error(): void
    {
        $params = [
            'merchantNo' => 'TEST001',
            'signature' => 'invalid_signature_value_here_1234',
        ];

        $response = $this->postJson('/api/balance/query', $params);

        $response->assertStatus(200);
        $response->assertJsonPath('code', 1006);
    }

    #[Test]
    public function balance_query_with_nonexistent_merchant_returns_error(): void
    {
        $params = [
            'merchantNo' => 'NONEXISTENT',
        ];

        $params['signature'] = SignatureHelper::generate($params, 'some_key');

        $response = $this->postJson('/api/balance/query', $params);

        $response->assertStatus(200);
        $response->assertJsonPath('code', 1002);
    }
}
