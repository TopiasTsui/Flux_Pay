<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EntityStatus;
use App\Models\Agent;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Merchant>
 */
class MerchantFactory extends Factory
{
    protected $model = Merchant::class;

    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'code' => fake()->unique()->bothify('MCH###??'),
            'name' => fake()->company(),
            'md5key' => Str::random(32),
            'currency_code' => 'PHP',
            'status' => EntityStatus::ACTIVE,
            'total_balance' => '0.00',
            'available_balance' => '0.00',
            'hold_balance' => '0.00',
            'white_ips' => [],
        ];
    }

    public function withBalance(string $amount): static
    {
        return $this->state(fn () => [
            'total_balance' => $amount,
            'available_balance' => $amount,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => EntityStatus::INACTIVE]);
    }
}
