<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Enums\OrderStatus;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AggregateDailyStatsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(private readonly ?string $date = null)
    {
        $this->onQueue('fluxpay-stats');
    }

    public function handle(): void
    {
        $date = $this->date
            ? Carbon::parse($this->date)->startOfDay()
            : Carbon::yesterday();
        $dateStr = $date->toDateString();
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        DB::transaction(function () use ($dateStr, $start, $end) {
            $this->aggregateOverall($dateStr, $start, $end);
            $this->aggregateByMerchant($dateStr, $start, $end);
            $this->aggregateByProvider($dateStr, $start, $end);
            $this->aggregateRevenue($dateStr, $start, $end);
            $this->aggregateRevenueByMerchant($dateStr, $start, $end);
            $this->aggregateRevenueByProvider($dateStr, $start, $end);
        });
    }

    private function aggregateOverall(string $date, Carbon $start, Carbon $end): void
    {
        $deposit = $this->txnAgg('deposit_orders', $start, $end);
        $withdraw = $this->txnAgg('withdraw_orders', $start, $end);

        DB::table('daily_transaction_stats')->upsert([
            [
                'date' => $date,
                'deposit_count' => $deposit['count'],
                'deposit_amount' => $deposit['amount'],
                'deposit_success_count' => $deposit['success_count'],
                'deposit_success_amount' => $deposit['success_amount'],
                'withdraw_count' => $withdraw['count'],
                'withdraw_amount' => $withdraw['amount'],
                'withdraw_success_count' => $withdraw['success_count'],
                'withdraw_success_amount' => $withdraw['success_amount'],
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['date'], [
            'deposit_count', 'deposit_amount', 'deposit_success_count', 'deposit_success_amount',
            'withdraw_count', 'withdraw_amount', 'withdraw_success_count', 'withdraw_success_amount',
            'updated_at',
        ]);
    }

    private function aggregateByMerchant(string $date, Carbon $start, Carbon $end): void
    {
        $deposit = $this->txnAggGrouped('deposit_orders', 'merchant_id', $start, $end);
        $withdraw = $this->txnAggGrouped('withdraw_orders', 'merchant_id', $start, $end);
        $merchantIds = array_unique([...array_keys($deposit), ...array_keys($withdraw)]);

        $rows = [];
        foreach ($merchantIds as $id) {
            $d = $deposit[$id] ?? $this->emptyAgg();
            $w = $withdraw[$id] ?? $this->emptyAgg();
            $rows[] = [
                'date' => $date,
                'merchant_id' => $id,
                'deposit_count' => $d['count'],
                'deposit_amount' => $d['amount'],
                'deposit_success_count' => $d['success_count'],
                'deposit_success_amount' => $d['success_amount'],
                'withdraw_count' => $w['count'],
                'withdraw_amount' => $w['amount'],
                'withdraw_success_count' => $w['success_count'],
                'withdraw_success_amount' => $w['success_amount'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows) {
            DB::table('daily_transaction_stats_by_merchant')->upsert($rows, ['date', 'merchant_id'], [
                'deposit_count', 'deposit_amount', 'deposit_success_count', 'deposit_success_amount',
                'withdraw_count', 'withdraw_amount', 'withdraw_success_count', 'withdraw_success_amount',
                'updated_at',
            ]);
        }
    }

    private function aggregateByProvider(string $date, Carbon $start, Carbon $end): void
    {
        $deposit = $this->txnAggGroupedByProvider('deposit_orders', $start, $end);
        $withdraw = $this->txnAggGroupedByProvider('withdraw_orders', $start, $end);
        $providerIds = array_unique([...array_keys($deposit), ...array_keys($withdraw)]);

        $rows = [];
        foreach ($providerIds as $id) {
            $d = $deposit[$id] ?? $this->emptyAgg();
            $w = $withdraw[$id] ?? $this->emptyAgg();
            $rows[] = [
                'date' => $date,
                'provider_id' => $id,
                'deposit_count' => $d['count'],
                'deposit_amount' => $d['amount'],
                'deposit_success_count' => $d['success_count'],
                'deposit_success_amount' => $d['success_amount'],
                'withdraw_count' => $w['count'],
                'withdraw_amount' => $w['amount'],
                'withdraw_success_count' => $w['success_count'],
                'withdraw_success_amount' => $w['success_amount'],
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows) {
            DB::table('daily_transaction_stats_by_provider')->upsert($rows, ['date', 'provider_id'], [
                'deposit_count', 'deposit_amount', 'deposit_success_count', 'deposit_success_amount',
                'withdraw_count', 'withdraw_amount', 'withdraw_success_count', 'withdraw_success_amount',
                'updated_at',
            ]);
        }
    }

    private function aggregateRevenue(string $date, Carbon $start, Carbon $end): void
    {
        $deposit = $this->revenueAgg('deposit_orders', $start, $end);
        $withdraw = $this->revenueAgg('withdraw_orders', $start, $end);

        $merchantFees = bcadd((string) $deposit['merchant_fee'], (string) $withdraw['merchant_fee'], 6);
        $providerFees = bcadd((string) $deposit['provider_fee'], (string) $withdraw['provider_fee'], 6);
        $agentCommissions = bcadd((string) $deposit['agent_fee'], (string) $withdraw['agent_fee'], 6);
        $providerAgentCommissions = bcadd((string) $deposit['provider_agent_fee'], (string) $withdraw['provider_agent_fee'], 6);
        $netProfit = bcsub(bcsub(bcsub($merchantFees, $providerFees, 6), $agentCommissions, 6), $providerAgentCommissions, 6);

        DB::table('daily_revenue_stats')->upsert([
            [
                'date' => $date,
                'total_revenue' => $merchantFees,
                'merchant_fees' => $merchantFees,
                'provider_fees' => $providerFees,
                'agent_commissions' => $agentCommissions,
                'net_profit' => $netProfit,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['date'], ['total_revenue', 'merchant_fees', 'provider_fees', 'agent_commissions', 'net_profit', 'updated_at']);
    }

    private function aggregateRevenueByMerchant(string $date, Carbon $start, Carbon $end): void
    {
        $deposit = $this->revenueAggGrouped('deposit_orders', 'merchant_id', $start, $end);
        $withdraw = $this->revenueAggGrouped('withdraw_orders', 'merchant_id', $start, $end);
        $merchantIds = array_unique([...array_keys($deposit), ...array_keys($withdraw)]);

        $rows = [];
        foreach ($merchantIds as $id) {
            $d = $deposit[$id] ?? $this->emptyRevenue();
            $w = $withdraw[$id] ?? $this->emptyRevenue();
            $rows[] = [
                'date' => $date,
                'merchant_id' => $id,
                'merchant_fees' => bcadd((string) $d['merchant_fee'], (string) $w['merchant_fee'], 6),
                'agent_commissions' => bcadd((string) $d['agent_fee'], (string) $w['agent_fee'], 6),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows) {
            DB::table('daily_revenue_stats_by_merchant')->upsert($rows, ['date', 'merchant_id'], ['merchant_fees', 'agent_commissions', 'updated_at']);
        }
    }

    private function aggregateRevenueByProvider(string $date, Carbon $start, Carbon $end): void
    {
        $deposit = $this->revenueAggGroupedByProvider('deposit_orders', $start, $end);
        $withdraw = $this->revenueAggGroupedByProvider('withdraw_orders', $start, $end);
        $providerIds = array_unique([...array_keys($deposit), ...array_keys($withdraw)]);

        $rows = [];
        foreach ($providerIds as $id) {
            $d = $deposit[$id] ?? $this->emptyRevenue();
            $w = $withdraw[$id] ?? $this->emptyRevenue();
            $rows[] = [
                'date' => $date,
                'provider_id' => $id,
                'provider_fees' => bcadd((string) $d['provider_fee'], (string) $w['provider_fee'], 6),
                'provider_agent_commissions' => bcadd((string) $d['provider_agent_fee'], (string) $w['provider_agent_fee'], 6),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        if ($rows) {
            DB::table('daily_revenue_stats_by_provider')->upsert($rows, ['date', 'provider_id'], ['provider_fees', 'provider_agent_commissions', 'updated_at']);
        }
    }

    private function txnAgg(string $table, Carbon $start, Carbon $end): array
    {
        $success = OrderStatus::SUCCESS->value;
        $row = DB::table($table)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('
                COUNT(*) AS cnt,
                COALESCE(SUM(order_amount), 0) AS amt,
                COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS scnt,
                COALESCE(SUM(CASE WHEN status = ? THEN order_amount ELSE 0 END), 0) AS samt
            ', [$success, $success])
            ->first();

        return [
            'count' => (int) ($row->cnt ?? 0),
            'amount' => (string) ($row->amt ?? '0'),
            'success_count' => (int) ($row->scnt ?? 0),
            'success_amount' => (string) ($row->samt ?? '0'),
        ];
    }

    private function txnAggGrouped(string $table, string $groupCol, Carbon $start, Carbon $end): array
    {
        $success = OrderStatus::SUCCESS->value;
        $rows = DB::table($table)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy($groupCol)
            ->selectRaw("$groupCol AS gid,
                COUNT(*) AS cnt,
                COALESCE(SUM(order_amount), 0) AS amt,
                COALESCE(SUM(CASE WHEN status = ? THEN 1 ELSE 0 END), 0) AS scnt,
                COALESCE(SUM(CASE WHEN status = ? THEN order_amount ELSE 0 END), 0) AS samt
            ", [$success, $success])
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->gid] = [
                'count' => (int) $r->cnt,
                'amount' => (string) $r->amt,
                'success_count' => (int) $r->scnt,
                'success_amount' => (string) $r->samt,
            ];
        }

        return $out;
    }

    private function txnAggGroupedByProvider(string $table, Carbon $start, Carbon $end): array
    {
        $success = OrderStatus::SUCCESS->value;
        $rows = DB::table("$table as o")
            ->join('provider_payment_types as ppt', 'o.provider_payment_type_id', '=', 'ppt.id')
            ->whereBetween('o.created_at', [$start, $end])
            ->groupBy('ppt.provider_id')
            ->selectRaw('ppt.provider_id AS gid,
                COUNT(*) AS cnt,
                COALESCE(SUM(o.order_amount), 0) AS amt,
                COALESCE(SUM(CASE WHEN o.status = ? THEN 1 ELSE 0 END), 0) AS scnt,
                COALESCE(SUM(CASE WHEN o.status = ? THEN o.order_amount ELSE 0 END), 0) AS samt
            ', [$success, $success])
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->gid] = [
                'count' => (int) $r->cnt,
                'amount' => (string) $r->amt,
                'success_count' => (int) $r->scnt,
                'success_amount' => (string) $r->samt,
            ];
        }

        return $out;
    }

    private function revenueAgg(string $table, Carbon $start, Carbon $end): array
    {
        $success = OrderStatus::SUCCESS->value;
        $row = DB::table($table)
            ->where('status', $success)
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('
                COALESCE(SUM(merchant_fee), 0) AS mf,
                COALESCE(SUM(provider_fee), 0) AS pf,
                COALESCE(SUM(agent_fee), 0) AS af,
                COALESCE(SUM(provider_agent_fee), 0) AS paf
            ')
            ->first();

        return [
            'merchant_fee' => (string) ($row->mf ?? '0'),
            'provider_fee' => (string) ($row->pf ?? '0'),
            'agent_fee' => (string) ($row->af ?? '0'),
            'provider_agent_fee' => (string) ($row->paf ?? '0'),
        ];
    }

    private function revenueAggGrouped(string $table, string $groupCol, Carbon $start, Carbon $end): array
    {
        $success = OrderStatus::SUCCESS->value;
        $rows = DB::table($table)
            ->where('status', $success)
            ->whereBetween('created_at', [$start, $end])
            ->groupBy($groupCol)
            ->selectRaw("$groupCol AS gid,
                COALESCE(SUM(merchant_fee), 0) AS mf,
                COALESCE(SUM(provider_fee), 0) AS pf,
                COALESCE(SUM(agent_fee), 0) AS af,
                COALESCE(SUM(provider_agent_fee), 0) AS paf
            ")
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->gid] = [
                'merchant_fee' => (string) $r->mf,
                'provider_fee' => (string) $r->pf,
                'agent_fee' => (string) $r->af,
                'provider_agent_fee' => (string) $r->paf,
            ];
        }

        return $out;
    }

    private function revenueAggGroupedByProvider(string $table, Carbon $start, Carbon $end): array
    {
        $success = OrderStatus::SUCCESS->value;
        $rows = DB::table("$table as o")
            ->join('provider_payment_types as ppt', 'o.provider_payment_type_id', '=', 'ppt.id')
            ->where('o.status', $success)
            ->whereBetween('o.created_at', [$start, $end])
            ->groupBy('ppt.provider_id')
            ->selectRaw('ppt.provider_id AS gid,
                COALESCE(SUM(o.merchant_fee), 0) AS mf,
                COALESCE(SUM(o.provider_fee), 0) AS pf,
                COALESCE(SUM(o.agent_fee), 0) AS af,
                COALESCE(SUM(o.provider_agent_fee), 0) AS paf
            ')
            ->get();

        $out = [];
        foreach ($rows as $r) {
            $out[(int) $r->gid] = [
                'merchant_fee' => (string) $r->mf,
                'provider_fee' => (string) $r->pf,
                'agent_fee' => (string) $r->af,
                'provider_agent_fee' => (string) $r->paf,
            ];
        }

        return $out;
    }

    private function emptyAgg(): array
    {
        return ['count' => 0, 'amount' => '0', 'success_count' => 0, 'success_amount' => '0'];
    }

    private function emptyRevenue(): array
    {
        return ['merchant_fee' => '0', 'provider_fee' => '0', 'agent_fee' => '0', 'provider_agent_fee' => '0'];
    }
}
