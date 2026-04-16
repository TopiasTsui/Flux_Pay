<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\EntityStatus;
use App\Models\Agent;
use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Provider>
 */
class ProviderFactory extends Factory
{
    protected $model = Provider::class;

    public function definition(): array
    {
        return [
            'agent_id' => Agent::factory(),
            'name' => fake()->company() . ' Payment',
            'provider_no' => fake()->unique()->bothify('PRV###??'),
            'vendor_id' => fake()->unique()->slug(2),
            'vendor_meta' => [],
            'currency_code' => 'PHP',
            'status' => EntityStatus::ACTIVE,
            'total_balance' => '0.000000',
            'available_balance' => '0.000000',
            'hold_balance' => '0.000000',
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => EntityStatus::INACTIVE]);
    }
}
