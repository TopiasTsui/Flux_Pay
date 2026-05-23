<?php

declare(strict_types=1);

namespace Tests\Feature\Schedule;

use App\Enums\OrderStatus;
use App\Jobs\AggregateDailyStatsJob;
use App\Models\DepositOrder;
use App\Models\Merchant;
use App\Models\Provider;
use App\Models\ProviderPaymentType;
use App\Models\WithdrawOrder;
use Database\Seeders\PaymentTypeSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AggregateDailyStatsTest extends TestCase
{
    use RefreshDatabase;

    private Merchant $merchant;

    private Provider $provider;

    private ProviderPaymentType $depositChannel;

    private ProviderPaymentType $withdrawChannel;

    private Carbon $yesterday;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PaymentTypeSeeder::class);
        $this->seed(TestDataSeeder::class);

        $this->merchant = Merchant::where('code', 'TEST001')->firstOrFail();
        $this->provider = Provider::where('vendor_id', 'testpay')->firstOrFail();
        $this->depositChannel = ProviderPaymentType::where('provider_id', $this->provider->id)
            ->where('type', 'deposit')->firstOrFail();
        $this->withdrawChannel = ProviderPaymentType::where('provider_id', $this->provider->id)
            ->where('type', 'withdraw')->firstOrFail();

        $this->yesterday = Carbon::yesterday()->setTime(12, 0, 0);
    }

    private function assertDecimalSame(string $expected, mixed $actual): void
    {
        $this->assertSame(
            0,
            bccomp($expected, (string) $actual, 6),
            "Decimal mismatch: expected {$expected}, got ".(string) $actual
        );
    }

    private function makeDeposit(int $status, string $amount, string $merchantFee = '0', string $providerFee = '0', string $agentFee = '0', string $providerAgentFee = '0'): DepositOrder
    {
        return DepositOrder::factory()->create([
            'merchant_id' => $this->merchant->id,
            'provider_payment_type_id' => $this->depositChannel->id,
            'order_amount' => $amount,
            'actual_amount' => $amount,
            'status' => $status,
            'merchant_fee' => $merchantFee,
            'provider_fee' => $providerFee,
            'agent_fee' => $agentFee,
            'provider_agent_fee' => $providerAgentFee,
            'created_at' => $this->yesterday,
            'updated_at' => $this->yesterday,
        ]);
    }

    private function makeWithdraw(int $status, string $amount, string $merchantFee = '0', string $providerFee = '0', string $agentFee = '0', string $providerAgentFee = '0'): WithdrawOrder
    {
        return WithdrawOrder::factory()->create([
            'merchant_id' => $this->merchant->id,
            'provider_payment_type_id' => $this->withdrawChannel->id,
            'order_amount' => $amount,
            'actual_amount' => $amount,
            'status' => $status,
            'merchant_fee' => $merchantFee,
            'provider_fee' => $providerFee,
            'agent_fee' => $agentFee,
            'provider_agent_fee' => $providerAgentFee,
            'created_at' => $this->yesterday,
            'updated_at' => $this->yesterday,
        ]);
    }

    #[Test]
    public function it_aggregates_overall_transaction_stats_for_yesterday(): void
    {
        $this->makeDeposit(OrderStatus::SUCCESS->value, '1000');
        $this->makeDeposit(OrderStatus::SUCCESS->value, '500');
        $this->makeDeposit(OrderStatus::FAILED->value, '300');
        $this->makeWithdraw(OrderStatus::SUCCESS->value, '700');
        $this->makeWithdraw(OrderStatus::PENDING->value, '200');

        (new AggregateDailyStatsJob)->handle();

        $row = DB::table('daily_transaction_stats')->where('date', $this->yesterday->toDateString())->first();
        $this->assertNotNull($row);
        $this->assertSame(3, (int) $row->deposit_count);
        $this->assertDecimalSame('1800', $row->deposit_amount);
        $this->assertSame(2, (int) $row->deposit_success_count);
        $this->assertDecimalSame('1500', $row->deposit_success_amount);
        $this->assertSame(2, (int) $row->withdraw_count);
        $this->assertDecimalSame('900', $row->withdraw_amount);
        $this->assertSame(1, (int) $row->withdraw_success_count);
        $this->assertDecimalSame('700', $row->withdraw_success_amount);
    }

    #[Test]
    public function it_aggregates_revenue_with_net_profit_formula(): void
    {
        $this->makeDeposit(OrderStatus::SUCCESS->value, '10000', merchantFee: '200', providerFee: '100', agentFee: '50', providerAgentFee: '20');
        $this->makeWithdraw(OrderStatus::SUCCESS->value, '5000', merchantFee: '100', providerFee: '50', agentFee: '25', providerAgentFee: '10');
        // Non-success rows must NOT contribute to revenue
        $this->makeDeposit(OrderStatus::FAILED->value, '99999', merchantFee: '999');

        (new AggregateDailyStatsJob)->handle();

        $row = DB::table('daily_revenue_stats')->where('date', $this->yesterday->toDateString())->first();
        $this->assertNotNull($row);
        $this->assertDecimalSame('300', $row->merchant_fees);
        $this->assertDecimalSame('150', $row->provider_fees);
        $this->assertDecimalSame('75', $row->agent_commissions);
        $this->assertDecimalSame('300', $row->total_revenue);
        // 300 - 150 - 75 - 30 (sum of provider_agent_fee) = 45
        $this->assertDecimalSame('45', $row->net_profit);
    }

    #[Test]
    public function it_aggregates_by_merchant_and_by_provider(): void
    {
        $this->makeDeposit(OrderStatus::SUCCESS->value, '1000', merchantFee: '20', providerFee: '10');

        (new AggregateDailyStatsJob)->handle();

        $merchantRow = DB::table('daily_transaction_stats_by_merchant')
            ->where('date', $this->yesterday->toDateString())
            ->where('merchant_id', $this->merchant->id)
            ->first();
        $this->assertNotNull($merchantRow);
        $this->assertSame(1, (int) $merchantRow->deposit_success_count);

        $providerRow = DB::table('daily_transaction_stats_by_provider')
            ->where('date', $this->yesterday->toDateString())
            ->where('provider_id', $this->provider->id)
            ->first();
        $this->assertNotNull($providerRow);
        $this->assertDecimalSame('1000', $providerRow->deposit_amount);

        $revenueByProvider = DB::table('daily_revenue_stats_by_provider')
            ->where('date', $this->yesterday->toDateString())
            ->where('provider_id', $this->provider->id)
            ->first();
        $this->assertNotNull($revenueByProvider);
        $this->assertDecimalSame('10', $revenueByProvider->provider_fees);
    }

    #[Test]
    public function rerunning_the_job_is_idempotent(): void
    {
        $this->makeDeposit(OrderStatus::SUCCESS->value, '1000', merchantFee: '20');

        (new AggregateDailyStatsJob)->handle();
        (new AggregateDailyStatsJob)->handle();

        $row = DB::table('daily_transaction_stats')->where('date', $this->yesterday->toDateString())->first();
        $this->assertSame(1, (int) $row->deposit_count);
        $this->assertDecimalSame('1000', $row->deposit_amount);

        $this->assertSame(1, DB::table('daily_transaction_stats')->where('date', $this->yesterday->toDateString())->count());
    }
}
