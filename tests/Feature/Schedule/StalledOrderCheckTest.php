<?php

declare(strict_types=1);

namespace Tests\Feature\Schedule;

use App\Enums\OrderStatus;
use App\Jobs\OrderQueryPollingJob;
use App\Models\DepositOrder;
use App\Models\Provider;
use App\Models\ProviderPaymentType;
use App\Models\WithdrawOrder;
use Database\Seeders\PaymentTypeSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StalledOrderCheckTest extends TestCase
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
    }

    #[Test]
    public function it_dispatches_polling_for_deposit_orders_within_the_window(): void
    {
        Queue::fake();

        $stalled = DepositOrder::factory()->create([
            'provider_payment_type_id' => $this->depositChannel->id,
            'status' => OrderStatus::SENT_TO_PROVIDER->value,
            'provider_apply_time' => Carbon::now()->subMinutes(10),
        ]);

        $this->artisan('flux:stalled-order-check')->assertSuccessful();

        Queue::assertPushed(OrderQueryPollingJob::class, function (OrderQueryPollingJob $job) use ($stalled) {
            return $job->orderType === 'deposit' && $job->orderId === $stalled->id;
        });
    }

    #[Test]
    public function it_skips_orders_younger_than_min_age(): void
    {
        Queue::fake();

        DepositOrder::factory()->create([
            'provider_payment_type_id' => $this->depositChannel->id,
            'status' => OrderStatus::SENT_TO_PROVIDER->value,
            'provider_apply_time' => Carbon::now()->subSeconds(30),
        ]);

        $this->artisan('flux:stalled-order-check')->assertSuccessful();

        Queue::assertNotPushed(OrderQueryPollingJob::class);
    }

    #[Test]
    public function it_skips_orders_older_than_max_age(): void
    {
        Queue::fake();

        DepositOrder::factory()->create([
            'provider_payment_type_id' => $this->depositChannel->id,
            'status' => OrderStatus::SENT_TO_PROVIDER->value,
            'provider_apply_time' => Carbon::now()->subDays(3),
        ]);

        $this->artisan('flux:stalled-order-check')->assertSuccessful();

        Queue::assertNotPushed(OrderQueryPollingJob::class);
    }

    #[Test]
    public function it_skips_orders_already_in_final_state(): void
    {
        Queue::fake();

        DepositOrder::factory()->create([
            'provider_payment_type_id' => $this->depositChannel->id,
            'status' => OrderStatus::SUCCESS->value,
            'provider_apply_time' => Carbon::now()->subMinutes(10),
        ]);

        $this->artisan('flux:stalled-order-check')->assertSuccessful();

        Queue::assertNotPushed(OrderQueryPollingJob::class);
    }

    #[Test]
    public function it_also_dispatches_for_stalled_withdraw_orders(): void
    {
        Queue::fake();

        $stalled = WithdrawOrder::factory()->create([
            'provider_payment_type_id' => $this->withdrawChannel->id,
            'status' => OrderStatus::SENT_TO_PROVIDER->value,
            'provider_apply_time' => Carbon::now()->subMinutes(10),
        ]);

        $this->artisan('flux:stalled-order-check')->assertSuccessful();

        Queue::assertPushed(OrderQueryPollingJob::class, function (OrderQueryPollingJob $job) use ($stalled) {
            return $job->orderType === 'withdraw' && $job->orderId === $stalled->id;
        });
    }
}
