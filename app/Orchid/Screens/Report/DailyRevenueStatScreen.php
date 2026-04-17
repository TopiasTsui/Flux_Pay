<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Report;

use App\Orchid\Concerns\HasFilters;
use App\Orchid\Layouts\Shared\FilterPanel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Orchid\Screen\Fields\DateRange;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class DailyRevenueStatScreen extends Screen
{
    use HasFilters;

    public $permission = 'platform.reports';

    public function name(): ?string
    {
        return __('Daily Revenue Statistics');
    }

    public function description(): ?string
    {
        return __('Revenue breakdown by day');
    }

    public function query(Request $request): iterable
    {
        $filter = (array) $request->input('filter', []);

        $query = DB::table('daily_revenue_stats')->orderBy('date', 'desc');

        if (!empty($filter['date']['start'])) {
            $query->where('date', '>=', $filter['date']['start']);
        }
        if (!empty($filter['date']['end'])) {
            $query->where('date', '<=', $filter['date']['end']);
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
        $filter = (array) request('filter', []);
        $summary = $this->buildFilterSummary($filter);

        return [
            FilterPanel::make(
                fields: [
                    DateRange::make('filter.date')->title(__('Date Range'))->value($filter['date'] ?? []),
                ],
                summary: $summary,
            ),

            Layout::table('stats', [
                TD::make('date', __('Date'))->sort(),
                TD::make('total_revenue', __('Total Revenue'))->alignRight()
                    ->render(fn ($r) => number_format((float) $r->total_revenue, 2)),
                TD::make('merchant_fees', __('Merchant Fees'))->alignRight()
                    ->render(fn ($r) => number_format((float) $r->merchant_fees, 2)),
                TD::make('provider_fees', __('Provider Fees'))->alignRight()
                    ->render(fn ($r) => number_format((float) $r->provider_fees, 2)),
                TD::make('agent_commissions', __('Agent Commissions'))->alignRight()
                    ->render(fn ($r) => number_format((float) $r->agent_commissions, 2)),
                TD::make('net_profit', __('Net Profit'))->alignRight()
                    ->render(fn ($r) => number_format((float) $r->net_profit, 2)),
            ]),
        ];
    }

    protected function filterRoute(): string
    {
        return 'platform.reports.revenue';
    }

    private function buildFilterSummary(array $f): array
    {
        $s = [];

        if (!empty($f['date']['start']) || !empty($f['date']['end'])) {
            $s[__('Date')] = ($f['date']['start'] ?? '…') . ' ~ ' . ($f['date']['end'] ?? '…');
        }

        return $s;
    }
}
