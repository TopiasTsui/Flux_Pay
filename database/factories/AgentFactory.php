<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AgentType;
use App\Enums\EntityStatus;
use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Agent>
 */
class AgentFactory extends Factory
{
    protected $model = Agent::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'types' => AgentType::MERCHANT,
            'level' => 1,
            'status' => EntityStatus::ACTIVE,
            'currency' => 'PHP',
            'total_balance' => '0.00',
            'available_balance' => '0.00',
            'hold_balance' => '0.00',
        ];
    }

    public function level(int $level): static
    {
        return $this->state(fn () => ['level' => $level]);
    }

    public function withParent(Agent $parent): static
    {
        return $this->state(fn () => [
            'parent_id' => $parent->id,
            'level' => $parent->level + 1,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => EntityStatus::INACTIVE]);
    }
}
