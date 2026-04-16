<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Report;

use Illuminate\Support\Facades\DB;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class DailyRevenueStatScreen extends Screen
{
    public $permission = 'platform.reports';

    public function name(): ?string
    {
        return 'Daily Revenue Statistics';
    }

    public function description(): ?string
    {
        return 'Revenue breakdown by day';
    }

    public function query(): iterable
    {
        $query = DB::table('daily_revenue_stats')->orderBy('date', 'desc');

        if (request('filter.date_start')) {
            $query->where('date', '>=', request('filter.date_start'));
        }
        if (request('filter.date_end')) {
            $query->where('date', '<=', request('filter.date_end'));
        }

        return [
            'stats' => $query->paginate(),
        ];
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('stats', [
                TD::make('date', 'Date')->sort(),
                TD::make('total_revenue', 'Total Revenue')->alignRight()
                    ->render(fn ($r) => number_format((float) $r->total_revenue, 2)),
                TD::make('merchant_fees', 'Merchant Fees')->alignRight()
                    ->render(fn ($r) => number_format((float) $r->merchant_fees, 2)),
                TD::make('provider_fees', 'Provider Fees')->alignRight()
                    ->render(fn ($r) => number_format((float) $r->provider_fees, 2)),
                TD::make('agent_commissions', 'Agent Commissions')->alignRight()
                    ->render(fn ($r) => number_format((float) $r->agent_commissions, 2)),
                TD::make('net_profit', 'Net Profit')->alignRight()
                    ->render(fn ($r) => number_format((float) $r->net_profit, 2)),
            ]),
        ];
    }
}
