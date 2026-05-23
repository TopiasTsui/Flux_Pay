<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\DepositOrder;
use App\Models\Merchant;
use App\Models\Provider;
use App\Models\WithdrawOrder;
use App\Services\Gateway\PaymentGatewayFactory;
use App\Services\Order\DepositService;
use App\Services\Order\WithdrawService;
use Database\Seeders\PaymentTypeSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Stubs\FakeGateway;
use Tests\TestCase;

class MerchantApiIdempotencyTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake(['*' => Http::response('success', 200)]);
        $this->seed(PaymentTypeSeeder::class);
        $this->seed(TestDataSeeder::class);

        Provider::query()->update([
            'available_balance' => '1000000.000000',
            'total_balance' => '1000000.000000',
        ]);

        $factory = $this->createMock(PaymentGatewayFactory::class);
        $factory->method('createFromProvider')->willReturn(new FakeGateway);
        $factory->method('createByVendorId')->willReturn(new FakeGateway);
        $factory->method('createByProviderPaymentTypeId')->willReturn(new FakeGateway);
        $this->app->instance(PaymentGatewayFactory::class, $factory);

        $this->merchant = Merchant::where('code', 'TEST001')->firstOrFail();
    }

    #[Test]
    public function duplicate_deposit_apply_returns_the_original_order(): void
    {
        $payload = [
            'merchant_order_no' => 'DUP-DEPOSIT-001',
            'amount' => '1000.00',
            'payment_type_code' => 'BANK_TRANSFER',
            'notify_url' => 'https://merchant.test/notify',
        ];

        $first = app(DepositService::class)->apply($this->merchant, $payload);
        $second = app(DepositService::class)->apply($this->merchant, $payload);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, DepositOrder::where('merchant_id', $this->merchant->id)
            ->where('merchant_order_no', 'DUP-DEPOSIT-001')->count());
    }

    #[Test]
    public function duplicate_withdraw_apply_returns_the_original_order(): void
    {
        $payload = [
            'merchant_order_no' => 'DUP-WITHDRAW-001',
            'amount' => '500.00',
            'bank_code' => 'BDO',
            'bank_account_name' => 'Test Payee',
            'bank_account_no' => '1234567890',
        ];

        $first = app(WithdrawService::class)->apply($this->merchant, $payload);
        $second = app(WithdrawService::class)->apply($this->merchant, $payload);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, WithdrawOrder::where('merchant_id', $this->merchant->id)
            ->where('merchant_order_no', 'DUP-WITHDRAW-001')->count());
    }

    #[Test]
    public function duplicate_withdraw_does_not_double_freeze_merchant_balance(): void
    {
        $before = $this->merchant->fresh()->available_balance;

        $payload = [
            'merchant_order_no' => 'DUP-WITHDRAW-002',
            'amount' => '500.00',
            'bank_code' => 'BDO',
            'bank_account_name' => 'Test Payee',
            'bank_account_no' => '1234567890',
        ];

        app(WithdrawService::class)->apply($this->merchant, $payload);
        $afterFirst = $this->merchant->fresh()->available_balance;

        app(WithdrawService::class)->apply($this->merchant, $payload);
        $afterSecond = $this->merchant->fresh()->available_balance;

        // First call freezes ~510 (500 + 2% fee); second call must not freeze again.
        $this->assertNotEquals($before, $afterFirst);
        $this->assertSame($afterFirst, $afterSecond);
    }

    #[Test]
    public function different_order_nos_create_separate_orders(): void
    {
        $base = [
            'amount' => '1000.00',
            'payment_type_code' => 'BANK_TRANSFER',
            'notify_url' => 'https://merchant.test/notify',
        ];

        $a = app(DepositService::class)->apply($this->merchant, [...$base, 'merchant_order_no' => 'UNIQ-A']);
        $b = app(DepositService::class)->apply($this->merchant, [...$base, 'merchant_order_no' => 'UNIQ-B']);

        $this->assertNotSame($a->id, $b->id);
        $this->assertSame(2, DepositOrder::where('merchant_id', $this->merchant->id)
            ->whereIn('merchant_order_no', ['UNIQ-A', 'UNIQ-B'])->count());
    }

    #[Test]
    public function unique_constraint_is_enforced_at_db_level(): void
    {
        $this->expectException(\Illuminate\Database\UniqueConstraintViolationException::class);

        DepositOrder::factory()->create([
            'merchant_id' => $this->merchant->id,
            'merchant_order_no' => 'CONSTRAINT-TEST',
        ]);

        // Direct insert bypassing the service-level check — DB must still reject.
        DepositOrder::factory()->create([
            'merchant_id' => $this->merchant->id,
            'merchant_order_no' => 'CONSTRAINT-TEST',
        ]);
    }
}
