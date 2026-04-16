<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Auth;

class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        // Administrator - no restrictions
        if ($user->hasAccess('platform.systems.roles')) {
            return;
        }

        $table = $model->getTable();

        // Manager - sees agents they created + all descendants
        if ($this->isManager($user)) {
            $this->applyManagerScope($builder, $model, $user);
            return;
        }

        // Merchant role
        if ($user->merchant_id) {
            $this->applyMerchantScope($builder, $table, $user->merchant_id);
            return;
        }

        // Agent role
        if ($user->agent_id) {
            $this->applyAgentScope($builder, $table, $user->agent_id);
            return;
        }
    }

    private function isManager($user): bool
    {
        $role = $user->roles()->where('slug', 'manager')->first();
        return $role !== null;
    }

    private function applyManagerScope(Builder $builder, Model $model, $user): void
    {
        $table = $model->getTable();
        $agentIds = $this->getManagerAgentIds($user->id);

        if ($table === 'agents') {
            $builder->whereIn('id', $agentIds);
        } elseif ($table === 'merchants') {
            $builder->whereIn('agent_id', $agentIds);
        } elseif ($table === 'providers') {
            $builder->whereIn('agent_id', $agentIds);
        } elseif (in_array($table, ['deposit_orders', 'withdraw_orders'])) {
            $merchantIds = \App\Models\Merchant::withoutGlobalScope(self::class)
                ->whereIn('agent_id', $agentIds)->pluck('id');
            $builder->whereIn('merchant_id', $merchantIds);
        } elseif ($table === 'merchant_wallet_records') {
            $merchantIds = \App\Models\Merchant::withoutGlobalScope(self::class)
                ->whereIn('agent_id', $agentIds)->pluck('id');
            $builder->whereIn('merchant_id', $merchantIds);
        } elseif ($table === 'agent_wallet_records') {
            $builder->whereIn('agent_id', $agentIds);
        }
    }

    private function getManagerAgentIds(int $userId): array
    {
        // Get agents created by this manager
        $topAgentIds = \App\Models\Agent::withoutGlobalScope(self::class)
            ->where('created_by', $userId)->pluck('id')->all();

        $allIds = [];
        foreach ($topAgentIds as $id) {
            $allIds = array_merge($allIds, $this->getDescendantAgentIds($id));
        }
        return $allIds;
    }

    private function applyMerchantScope(Builder $builder, string $table, int $merchantId): void
    {
        if ($table === 'merchants') {
            $builder->where('id', $merchantId);
        } elseif (in_array($table, ['deposit_orders', 'withdraw_orders', 'merchant_wallet_records', 'merchant_payment_types', 'merchant_provider_payment_types'])) {
            $builder->where('merchant_id', $merchantId);
        }
    }

    private function applyAgentScope(Builder $builder, string $table, int $agentId): void
    {
        $agentIds = $this->getDescendantAgentIds($agentId);

        if ($table === 'agents') {
            $builder->whereIn('id', $agentIds);
        } elseif ($table === 'merchants') {
            $builder->whereIn('agent_id', $agentIds);
        } elseif ($table === 'providers') {
            $builder->whereIn('agent_id', $agentIds);
        } elseif (in_array($table, ['deposit_orders', 'withdraw_orders'])) {
            $merchantIds = \App\Models\Merchant::withoutGlobalScope(self::class)
                ->whereIn('agent_id', $agentIds)->pluck('id');
            $builder->whereIn('merchant_id', $merchantIds);
        } elseif ($table === 'agent_wallet_records') {
            $builder->whereIn('agent_id', $agentIds);
        } elseif ($table === 'merchant_wallet_records') {
            $merchantIds = \App\Models\Merchant::withoutGlobalScope(self::class)
                ->whereIn('agent_id', $agentIds)->pluck('id');
            $builder->whereIn('merchant_id', $merchantIds);
        }
    }

    private function getDescendantAgentIds(int $agentId): array
    {
        $ids = [$agentId];
        $children = \App\Models\Agent::withoutGlobalScope(self::class)
            ->where('parent_id', $agentId)->pluck('id')->all();
        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getDescendantAgentIds($childId));
        }
        return $ids;
    }
}
