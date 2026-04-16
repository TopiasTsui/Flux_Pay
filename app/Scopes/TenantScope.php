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

        if (! $user) {
            return;
        }

        if ($user->hasAccess('platform.systems.roles')) {
            // Administrator — see everything
            return;
        }

        if ($user->merchant_id) {
            $this->applyMerchantScope($builder, $model, $user->merchant_id);
        } elseif ($user->agent_id) {
            $this->applyAgentScope($builder, $model, $user->agent_id);
        }
    }

    private function applyMerchantScope(Builder $builder, Model $model, int $merchantId): void
    {
        $table = $model->getTable();

        if ($table === 'merchants') {
            $builder->where('id', $merchantId);
        } elseif (in_array($table, ['deposit_orders', 'withdraw_orders', 'merchant_wallet_records', 'merchant_payment_types', 'merchant_provider_payment_types'])) {
            $builder->where('merchant_id', $merchantId);
        }
    }

    private function applyAgentScope(Builder $builder, Model $model, int $agentId): void
    {
        $table = $model->getTable();
        $agentIds = $this->getDescendantAgentIds($agentId);

        if ($table === 'agents') {
            $builder->whereIn('id', $agentIds);
        } elseif ($table === 'merchants') {
            $builder->whereIn('agent_id', $agentIds);
        } elseif ($table === 'providers') {
            $builder->whereIn('agent_id', $agentIds);
        } elseif (in_array($table, ['deposit_orders', 'withdraw_orders'])) {
            $merchantIds = \App\Models\Merchant::withoutGlobalScope(self::class)
                ->whereIn('agent_id', $agentIds)
                ->pluck('id');
            $builder->whereIn('merchant_id', $merchantIds);
        } elseif ($table === 'agent_wallet_records') {
            $builder->whereIn('agent_id', $agentIds);
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
