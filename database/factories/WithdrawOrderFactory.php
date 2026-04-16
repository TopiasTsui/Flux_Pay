<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CallbackStatus;
use App\Enums\FundStatus;
use App\Enums\OrderStatus;
use App\Helpers\OrderNumberGenerator;
use App\Models\Merchant;
use App\Models\WithdrawOrder;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WithdrawOrder>
 */
class WithdrawOrderFactory extends Factory
{
    protected $model = WithdrawOrder::class;

    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'merchant_order_no' => fake()->unique()->numerify('WDR##########'),
            'system_order_no' => OrderNumberGenerator::generate('W'),
            'order_amount' => fake()->randomFloat(2, 100, 50000),
            'actual_amount' => fn (array $attrs) => $attrs['order_amount'],
            'merchant_fee' => '0.00',
            'provider_fee' => '0.00',
            'agent_fee' => '0.00',
            'agent_fee_map' => [],
            'provider_agent_fee' => '0.00',
            'provider_agent_fee_map' => [],
            'total_debit' => fn (array $attrs) => $attrs['order_amount'],
            'bank_code' => 'BDO',
            'bank_account_name' => fake()->name(),
            'bank_account_no' => fake()->numerify('##########'),
            'currency' => 'PHP',
            'status' => OrderStatus::PENDING,
            'callback_status' => CallbackStatus::PENDING,
            'fund_status' => FundStatus::PENDING,
        ];
    }

    public function success(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::SUCCESS,
            'callback_status' => CallbackStatus::PROVIDER_SUCCESS,
            'fund_status' => FundStatus::SETTLED,
            'fund_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => OrderStatus::FAILED,
        ]);
    }
}
