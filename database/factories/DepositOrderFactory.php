<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CallbackStatus;
use App\Enums\FundStatus;
use App\Enums\OrderStatus;
use App\Helpers\OrderNumberGenerator;
use App\Models\DepositOrder;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DepositOrder>
 */
class DepositOrderFactory extends Factory
{
    protected $model = DepositOrder::class;

    public function definition(): array
    {
        return [
            'merchant_id' => Merchant::factory(),
            'merchant_order_no' => fake()->unique()->numerify('ORD##########'),
            'system_order_no' => OrderNumberGenerator::generate('D'),
            'order_amount' => fake()->randomFloat(2, 100, 50000),
            'actual_amount' => fn (array $attrs) => $attrs['order_amount'],
            'merchant_fee' => '0.000000',
            'provider_fee' => '0.000000',
            'agent_fee' => '0.000000',
            'agent_fee_map' => [],
            'provider_agent_fee' => '0.000000',
            'provider_agent_fee_map' => [],
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
