<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class AgentCommissionResult
{
    /**
     * @param  string  $total  Total agent commission amount.
     * @param  array<int, string>  $agentFeeMap  Map of agent_id => commission amount.
     */
    public function __construct(
        public string $total,
        public array $agentFeeMap,
    ) {}
}
