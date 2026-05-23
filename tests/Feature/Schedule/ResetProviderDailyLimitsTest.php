<?php

declare(strict_types=1);

namespace Tests\Feature\Schedule;

use App\Models\ProviderPaymentType;
use Database\Seeders\PaymentTypeSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ResetProviderDailyLimitsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentTypeSeeder::class);
        $this->seed(TestDataSeeder::class);
    }

    #[Test]
    public function it_resets_channels_whose_reset_time_matches_now(): void
    {
        $now = Carbon::now()->format('H:i');
        $channel = ProviderPaymentType::query()->first();
        $channel->update([
            'reset_time' => $now,
            'current_daily_amount' => '12345.000000',
        ]);

        $this->artisan('flux:reset-provider-daily-limits')->assertSuccessful();

        $this->assertSame(0, bccomp('0', (string) $channel->fresh()->current_daily_amount, 2));
    }

    #[Test]
    public function it_skips_channels_with_a_different_reset_time(): void
    {
        $offHour = Carbon::now()->addHours(3)->format('H:i');
        $channel = ProviderPaymentType::query()->first();
        $channel->update([
            'reset_time' => $offHour,
            'current_daily_amount' => '777.000000',
        ]);

        $this->artisan('flux:reset-provider-daily-limits')->assertSuccessful();

        $this->assertSame(0, bccomp('777', (string) $channel->fresh()->current_daily_amount, 2));
    }

    #[Test]
    public function force_option_resets_every_channel_regardless_of_time(): void
    {
        $offHour = Carbon::now()->addHours(3)->format('H:i');
        $channels = ProviderPaymentType::query()->get();
        foreach ($channels as $c) {
            $c->update([
                'reset_time' => $offHour,
                'current_daily_amount' => '500.000000',
            ]);
        }

        $this->artisan('flux:reset-provider-daily-limits', ['--force' => true])->assertSuccessful();

        foreach (ProviderPaymentType::query()->get() as $c) {
            $this->assertSame(0, bccomp('0', (string) $c->current_daily_amount, 2));
        }
    }
}
