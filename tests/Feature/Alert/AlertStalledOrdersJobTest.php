<?php

declare(strict_types=1);

namespace Tests\Feature\Alert;

use App\Enums\OrderStatus;
use App\Jobs\AlertStalledOrdersJob;
use App\Models\DepositOrder;
use App\Models\Provider;
use App\Models\ProviderPaymentType;
use App\Models\WithdrawOrder;
use App\Services\Alert\AlertDispatcher;
use Database\Seeders\PaymentTypeSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AlertStalledOrdersJobTest extends TestCase
{
    use RefreshDatabase;

    private ProviderPaymentType $depositChannel;

    private ProviderPaymentType $withdrawChannel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentTypeSeeder::class);
        $this->seed(TestDataSeeder::class);

        $provider = Provider::where('vendor_id', 'testpay')->firstOrFail();
        $this->depositChannel = ProviderPaymentType::where('provider_id', $provider->id)
            ->where('type', 'deposit')->firstOrFail();
        $this->withdrawChannel = ProviderPaymentType::where('provider_id', $provider->id)
            ->where('type', 'withdraw')->firstOrFail();

        config([
            'fluxpay.alert_stalled_threshold_hours' => 4,
            'fluxpay.alert_dedupe_ttl_seconds' => 86400,
        ]);

        Cache::flush();
    }

    private function mockDispatcher(): \PHPUnit\Framework\MockObject\MockObject
    {
        $mock = $this->createMock(AlertDispatcher::class);
        $this->app->instance(AlertDispatcher::class, $mock);

        return $mock;
    }

    #[Test]
    public function alerts_for_orders_older_than_threshold(): void
    {
        $order = DepositOrder::factory()->create([
            'provider_payment_type_id' => $this->depositChannel->id,
            'status' => OrderStatus::SENT_TO_PROVIDER->value,
            'provider_apply_time' => Carbon::now()->subHours(5),
        ]);

        $dispatcher = $this->mockDispatcher();
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                'Stalled deposit order',
                $this->stringContains($order->system_order_no),
                $this->callback(fn ($ctx) => ($ctx['order_id'] ?? null) === $order->id),
            );

        (new AlertStalledOrdersJob)->handle(app(AlertDispatcher::class));
    }

    #[Test]
    public function does_not_alert_for_orders_under_threshold(): void
    {
        DepositOrder::factory()->create([
            'provider_payment_type_id' => $this->depositChannel->id,
            'status' => OrderStatus::SENT_TO_PROVIDER->value,
            'provider_apply_time' => Carbon::now()->subHours(2),
        ]);

        $dispatcher = $this->mockDispatcher();
        $dispatcher->expects($this->never())->method('dispatch');

        (new AlertStalledOrdersJob)->handle(app(AlertDispatcher::class));
    }

    #[Test]
    public function does_not_alert_for_orders_in_final_state(): void
    {
        DepositOrder::factory()->create([
            'provider_payment_type_id' => $this->depositChannel->id,
            'status' => OrderStatus::SUCCESS->value,
            'provider_apply_time' => Carbon::now()->subHours(5),
        ]);

        $dispatcher = $this->mockDispatcher();
        $dispatcher->expects($this->never())->method('dispatch');

        (new AlertStalledOrdersJob)->handle(app(AlertDispatcher::class));
    }

    #[Test]
    public function dedupes_within_ttl(): void
    {
        DepositOrder::factory()->create([
            'provider_payment_type_id' => $this->depositChannel->id,
            'status' => OrderStatus::SENT_TO_PROVIDER->value,
            'provider_apply_time' => Carbon::now()->subHours(5),
        ]);

        $dispatcher = $this->mockDispatcher();
        $dispatcher->expects($this->once())->method('dispatch');

        (new AlertStalledOrdersJob)->handle(app(AlertDispatcher::class));
        (new AlertStalledOrdersJob)->handle(app(AlertDispatcher::class));
    }

    #[Test]
    public function also_picks_up_stalled_withdraw_orders(): void
    {
        WithdrawOrder::factory()->create([
            'provider_payment_type_id' => $this->withdrawChannel->id,
            'status' => OrderStatus::SENT_TO_PROVIDER->value,
            'provider_apply_time' => Carbon::now()->subHours(5),
        ]);

        $dispatcher = $this->mockDispatcher();
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with('Stalled withdraw order', $this->anything(), $this->anything());

        (new AlertStalledOrdersJob)->handle(app(AlertDispatcher::class));
    }
}
