<?php

declare(strict_types=1);

namespace Tests\Feature\Order;

use App\DTOs\Gateway\DepositCallbackResult;
use App\Enums\FundStatus;
use App\Enums\OrderStatus;
use App\Enums\WalletOperationType;
use App\Events\Order\DepositCallbackReceived;
use App\Events\Order\DepositFundSettled;
use App\Models\DepositOrder;
use App\Models\Merchant;
use App\Models\MerchantWalletRecord;
use App\Models\Provider;
use App\Services\Gateway\PaymentGatewayFactory;
use App\Services\Order\DepositService;
use Database\Seeders\PaymentTypeSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\Stubs\FakeGateway;
use Tests\TestCase;

class DepositCallbackTest extends TestCase
{
    use RefreshDatabase;

    private FakeGateway $gateway;

    protected function setUp(): void
    {
        parent::setUp();

        // Merchant success notifications must never hit the real network.
        Http::fake(['*' => Http::response('success', 200)]);

        $this->seed(PaymentTypeSeeder::class);
        $this->seed(TestDataSeeder::class);

        // Settlement debits the provider wallet, so it must be funded.
        Provider::query()->update([
            'available_balance' => '1000000.000000',
            'total_balance' => '1000000.000000',
        ]);

        $this->gateway = new FakeGateway;
        $factory = $this->createMock(PaymentGatewayFactory::class);
        $factory->method('createFromProvider')->willReturn($this->gateway);
        $factory->method('createByVendorId')->willReturn($this->gateway);
        $factory->method('createByProviderPaymentTypeId')->willReturn($this->gateway);
        $this->app->instance(PaymentGatewayFactory::class, $factory);
    }

    private function createSentOrder(): DepositOrder
    {
        $merchant = Merchant::where('code', 'TEST001')->firstOrFail();

        return app(DepositService::class)->apply($merchant, [
            'merchant_order_no' => 'DEP_'.uniqid(),
            'amount' => '1000.00',
            'payment_type_code' => 'BANK_TRANSFER',
            'notify_url' => 'https://merchant.test/notify',
        ]);
    }

    private function successResult(DepositOrder $order): DepositCallbackResult
    {
        return new DepositCallbackResult(
            success: true,
            systemOrderNo: $order->system_order_no,
            providerOrderNo: 'PROV_123',
            status: OrderStatus::SUCCESS,
            actualAmount: '1000.00',
        );
    }

    #[Test]
    public function successful_callback_advances_status_and_settles_funds(): void
    {
        $order = $this->createSentOrder();
        $this->assertSame(OrderStatus::SENT_TO_PROVIDER->value, $order->status);
        $this->assertSame(FundStatus::PENDING->value, $order->fund_status);

        DepositCallbackReceived::dispatch($order, $this->successResult($order));

        $order->refresh();
        // Regression guard: before the fix, status stayed at SENT_TO_PROVIDER.
        $this->assertSame(OrderStatus::SUCCESS->value, $order->status);
        $this->assertSame(FundStatus::SETTLED->value, $order->fund_status);
    }

    #[Test]
    public function failed_callback_marks_order_failed_without_settling(): void
    {
        $order = $this->createSentOrder();

        DepositCallbackReceived::dispatch($order, new DepositCallbackResult(
            success: true,
            systemOrderNo: $order->system_order_no,
            status: OrderStatus::FAILED,
        ));

        $order->refresh();
        $this->assertSame(OrderStatus::FAILED->value, $order->status);
        $this->assertSame(FundStatus::PENDING->value, $order->fund_status);
    }

    #[Test]
    public function duplicate_callback_does_not_settle_twice(): void
    {
        $order = $this->createSentOrder();
        $merchantId = $order->merchant_id;

        DepositCallbackReceived::dispatch($order, $this->successResult($order));
        $balanceAfterFirst = Merchant::findOrFail($merchantId)->available_balance;

        // Replay the same provider callback.
        DepositCallbackReceived::dispatch($order->fresh(), $this->successResult($order));
        $balanceAfterSecond = Merchant::findOrFail($merchantId)->available_balance;

        $this->assertEquals($balanceAfterFirst, $balanceAfterSecond);
        // The order must have been credited to the merchant exactly once.
        $this->assertSame(1, MerchantWalletRecord::where('system_order_no', $order->system_order_no)
            ->where('type_code', WalletOperationType::DEPOSIT_INCOME->value)
            ->count());
    }

    #[Test]
    public function manual_query_pulls_final_status_and_settles(): void
    {
        $order = $this->createSentOrder();
        $this->gateway->depositCallbackResult = $this->successResult($order);

        $result = app(DepositService::class)->manualQuery($order);

        $this->assertTrue($result->success);
        $order->refresh();
        $this->assertSame(OrderStatus::SUCCESS->value, $order->status);
        $this->assertSame(FundStatus::SETTLED->value, $order->fund_status);
        $this->assertDatabaseHas('order_logs', [
            'orderable_id' => $order->id,
            'action' => 'manual_query',
        ]);
    }

    #[Test]
    public function resend_merchant_notification_redispatches_for_successful_order(): void
    {
        $order = $this->createSentOrder();
        DepositCallbackReceived::dispatch($order, $this->successResult($order));
        $order->refresh();
        $this->assertSame(OrderStatus::SUCCESS->value, $order->status);

        Event::fake([DepositFundSettled::class]);

        $ok = app(DepositService::class)->resendMerchantNotification($order);

        $this->assertTrue($ok);
        Event::assertDispatched(DepositFundSettled::class);
        $this->assertDatabaseHas('order_logs', [
            'orderable_id' => $order->id,
            'action' => 'manual_callback',
        ]);
    }

    #[Test]
    public function resend_merchant_notification_refuses_non_successful_order(): void
    {
        $order = $this->createSentOrder();

        $this->assertFalse(app(DepositService::class)->resendMerchantNotification($order));
    }

    #[Test]
    public function each_listener_is_registered_exactly_once(): void
    {
        // Regression guard: order listeners must be registered only via Laravel's
        // automatic discovery, never also manually in AppServiceProvider.
        $raw = Event::getRawListeners();
        $deposit = $raw[DepositCallbackReceived::class] ?? [];

        $this->assertCount(2, $deposit); // SettleDepositFunds + LogOrderStatusChange
    }
}
