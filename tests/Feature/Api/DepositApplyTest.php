<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Helpers\SignatureHelper;
use App\Models\Merchant;
use App\Services\Gateway\PaymentGatewayFactory;
use Database\Seeders\PaymentTypeSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\Stubs\FakeGateway;
use Tests\TestCase;

class DepositApplyTest extends TestCase
{
    use RefreshDatabase;

    private string $md5key = 'test_secret_key_123';

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PaymentTypeSeeder::class);
        $this->seed(TestDataSeeder::class);

        // Mock the gateway factory to return our fake gateway
        $fakeGateway = new FakeGateway();
        $mockFactory = $this->createMock(PaymentGatewayFactory::class);
        $mockFactory->method('createFromProvider')->willReturn($fakeGateway);
        $mockFactory->method('createByVendorId')->willReturn($fakeGateway);
        $mockFactory->method('createByProviderPaymentTypeId')->willReturn($fakeGateway);
        $this->app->instance(PaymentGatewayFactory::class, $mockFactory);
    }

    #[Test]
    public function deposit_apply_with_valid_signature_returns_success(): void
    {
        $params = [
            'merchantNo' => 'TEST001',
            'orderNo' => 'TEST_DEP_' . time(),
            'amount' => '1000.00',
            'paymentTypeCode' => 'BANK_TRANSFER',
        ];

        $params['signature'] = SignatureHelper::generate($params, $this->md5key);

        $response = $this->postJson('/api/deposit/apply', $params);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'code',
            'message',
            'data',
            'timestamp',
        ]);
        $response->assertJsonPath('code', 0);
        $response->assertJsonPath('message', 'Success');
    }

    #[Test]
    public function deposit_apply_with_invalid_signature_returns_error(): void
    {
        $params = [
            'merchantNo' => 'TEST001',
            'orderNo' => 'TEST_DEP_' . time(),
            'amount' => '1000.00',
            'signature' => 'invalid_signature_value_here_1234',
        ];

        $response = $this->postJson('/api/deposit/apply', $params);

        $response->assertStatus(200);
        $response->assertJsonPath('code', 1006);
    }

    #[Test]
    public function deposit_apply_with_missing_merchant_returns_error(): void
    {
        $params = [
            'merchantNo' => 'NONEXISTENT',
            'orderNo' => 'TEST_DEP_' . time(),
            'amount' => '1000.00',
        ];

        $params['signature'] = SignatureHelper::generate($params, $this->md5key);

        $response = $this->postJson('/api/deposit/apply', $params);

        $response->assertStatus(200);
        $response->assertJsonPath('code', 1002);
        $response->assertJsonPath('message', 'Merchant not found');
    }

    #[Test]
    public function deposit_apply_without_merchant_no_returns_error(): void
    {
        $params = [
            'orderNo' => 'TEST_DEP_' . time(),
            'amount' => '1000.00',
            'signature' => 'some_signature',
        ];

        $response = $this->postJson('/api/deposit/apply', $params);

        $response->assertStatus(200);
        $response->assertJsonPath('code', 1001);
    }

    #[Test]
    public function deposit_apply_with_inactive_merchant_returns_error(): void
    {
        $merchant = Merchant::where('code', 'TEST001')->first();
        $merchant->update(['status' => 0]);

        $params = [
            'merchantNo' => 'TEST001',
            'orderNo' => 'TEST_DEP_' . time(),
            'amount' => '1000.00',
        ];

        $params['signature'] = SignatureHelper::generate($params, $this->md5key);

        $response = $this->postJson('/api/deposit/apply', $params);

        $response->assertStatus(200);
        $response->assertJsonPath('code', 1003);
    }
}
