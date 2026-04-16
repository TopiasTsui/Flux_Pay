<?php

declare(strict_types=1);

namespace App\Contracts\Repositories;

use App\Models\Agent;

interface AgentRepositoryInterface
{
    public function find(int $id): ?Agent;

    public function findOrFail(int $id): Agent;

    public function create(array $data): Agent;

    public function update(int $id, array $data): bool;

    public function delete(int $id): bool;

    /**
     * Get all descendant agent IDs (children, grandchildren, etc.).
     *
     * @return array<int>
     */
    public function getDescendantIds(int $agentId): array;

    /**
     * Lock the agent row for update within a transaction.
     */
    public function lockForUpdate(int $id): ?Agent;
}
