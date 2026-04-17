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

class DailyTransactionStatScreen extends Screen
{
    use HasFilters;

    public $permission = 'platform.reports';

    public function name(): ?string
    {
        return __('Daily Transaction Statistics');
    }

    public function description(): ?string
    {
        return __('Transaction volume and success rates by day');
    }

    public function query(Request $request): iterable
    {
        $filter = (array) $request->input('filter', []);

        $query = DB::table('daily_transaction_stats')->orderBy('date', 'desc');

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
                TD::make('deposit_count', __('Deposit Count'))->alignRight(),
                TD::make('deposit_amount', __('Deposit Amount'))->alignRight()
                    ->render(fn ($r) => number_format((float) $r->deposit_amount, 2)),
                TD::make('deposit_success_count', __('Deposit Success'))->alignRight(),
                TD::make('deposit_success_amount', __('Success Amount'))->alignRight()
                    ->render(fn ($r) => number_format((float) $r->deposit_success_amount, 2)),
                TD::make('deposit_rate', __('Deposit Rate'))->alignRight()
                    ->render(fn ($r) => $r->deposit_count > 0
                        ? round($r->deposit_success_count / $r->deposit_count * 100, 1) . '%'
                        : '-'),
                TD::make('withdraw_count', __('Withdraw Count'))->alignRight(),
                TD::make('withdraw_amount', __('Withdraw Amount'))->alignRight()
                    ->render(fn ($r) => number_format((float) $r->withdraw_amount, 2)),
                TD::make('withdraw_success_count', __('Withdraw Success'))->alignRight(),
                TD::make('withdraw_rate', __('Withdraw Rate'))->alignRight()
                    ->render(fn ($r) => $r->withdraw_count > 0
                        ? round($r->withdraw_success_count / $r->withdraw_count * 100, 1) . '%'
                        : '-'),
            ]),
        ];
    }

    protected function filterRoute(): string
    {
        return 'platform.reports.transactions';
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
