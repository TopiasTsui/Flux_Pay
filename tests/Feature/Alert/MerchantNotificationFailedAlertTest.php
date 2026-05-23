<?php

declare(strict_types=1);

namespace Tests\Feature\Alert;

use App\Enums\CallbackStatus;
use App\Jobs\MerchantNotificationJob;
use App\Models\DepositOrder;
use App\Models\Provider;
use App\Models\ProviderPaymentType;
use App\Services\Alert\AlertDispatcher;
use Database\Seeders\PaymentTypeSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MerchantNotificationFailedAlertTest extends TestCase
{
    use RefreshDatabase;

    private DepositOrder $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentTypeSeeder::class);
        $this->seed(TestDataSeeder::class);

        $provider = Provider::where('vendor_id', 'testpay')->firstOrFail();
        $channel = ProviderPaymentType::where('provider_id', $provider->id)
            ->where('type', 'deposit')->firstOrFail();

        $this->order = DepositOrder::factory()->create([
            'provider_payment_type_id' => $channel->id,
        ]);
    }

    #[Test]
    public function failed_handler_dispatches_alert_and_updates_callback_status(): void
    {
        $dispatcher = $this->createMock(AlertDispatcher::class);
        $dispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                'Merchant callback exhausted retries',
                $this->stringContains('attempts'),
                $this->callback(fn ($ctx) => ($ctx['order_id'] ?? null) === $this->order->id
                    && ($ctx['order_type'] ?? null) === 'deposit'),
            );
        $this->app->instance(AlertDispatcher::class, $dispatcher);

        $job = new MerchantNotificationJob(
            url: 'https://merchant.test/notify',
            data: ['foo' => 'bar'],
            md5key: 'k',
            orderType: 'deposit',
            orderId: $this->order->id,
        );

        $job->failed(new \RuntimeException('boom'));

        $this->assertSame(
            CallbackStatus::MERCHANT_FAILED->value,
            $this->order->fresh()->callback_status,
        );
    }

    #[Test]
    public function dispatcher_exception_does_not_block_callback_status_update(): void
    {
        $dispatcher = $this->createMock(AlertDispatcher::class);
        $dispatcher->method('dispatch')->willThrowException(new \RuntimeException('alert broken'));
        $this->app->instance(AlertDispatcher::class, $dispatcher);

        $job = new MerchantNotificationJob(
            url: 'https://merchant.test/notify',
            data: ['foo' => 'bar'],
            md5key: 'k',
            orderType: 'deposit',
            orderId: $this->order->id,
        );

        $job->failed(null);

        // callback_status must still flip even though the alert failed.
        $this->assertSame(
            CallbackStatus::MERCHANT_FAILED->value,
            $this->order->fresh()->callback_status,
        );
    }
}
