<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Contracts\Repositories\AgentRepositoryInterface;
use App\Models\Agent;

class AgentRepository implements AgentRepositoryInterface
{
    public function find(int $id): ?Agent
    {
        return Agent::find($id);
    }

    public function findOrFail(int $id): Agent
    {
        return Agent::findOrFail($id);
    }

    public function create(array $data): Agent
    {
        return Agent::create($data);
    }

    public function update(int $id, array $data): bool
    {
        return (bool) Agent::where('id', $id)->update($data);
    }

    public function delete(int $id): bool
    {
        return (bool) Agent::where('id', $id)->delete();
    }

    public function getDescendantIds(int $agentId): array
    {
        $descendants = [];
        $queue = [$agentId];

        while (! empty($queue)) {
            $parentId = array_shift($queue);
            $childIds = Agent::where('parent_id', $parentId)->pluck('id')->all();

            foreach ($childIds as $childId) {
                $descendants[] = $childId;
                $queue[] = $childId;
            }
        }

        return $descendants;
    }

    public function lockForUpdate(int $id): ?Agent
    {
        return Agent::lockForUpdate()->find($id);
    }
}
