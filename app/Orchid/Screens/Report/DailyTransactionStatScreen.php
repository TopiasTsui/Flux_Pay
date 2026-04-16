<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Report;

use Illuminate\Support\Facades\DB;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class DailyTransactionStatScreen extends Screen
{
    public $permission = 'platform.reports';

    public function name(): ?string
    {
        return 'Daily Transaction Statistics';
    }

    public function description(): ?string
    {
        return 'Transaction volume and success rates by day';
    }

    public function query(): iterable
    {
        $query = DB::table('daily_transaction_stats')->orderBy('date', 'desc');

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
                TD::make('deposit_count', 'Deposit Count')->alignRight(),
                TD::make('deposit_amount', 'Deposit Amount')->alignRight()
                    ->render(fn ($r) => number_format((float) $r->deposit_amount, 2)),
                TD::make('deposit_success_count', 'Deposit Success')->alignRight(),
                TD::make('deposit_success_amount', 'Success Amount')->alignRight()
                    ->render(fn ($r) => number_format((float) $r->deposit_success_amount, 2)),
                TD::make('deposit_rate', 'Deposit Rate')->alignRight()
                    ->render(fn ($r) => $r->deposit_count > 0
                        ? round($r->deposit_success_count / $r->deposit_count * 100, 1) . '%'
                        : '-'),
                TD::make('withdraw_count', 'Withdraw Count')->alignRight(),
                TD::make('withdraw_amount', 'Withdraw Amount')->alignRight()
                    ->render(fn ($r) => number_format((float) $r->withdraw_amount, 2)),
                TD::make('withdraw_success_count', 'Withdraw Success')->alignRight(),
                TD::make('withdraw_rate', 'Withdraw Rate')->alignRight()
                    ->render(fn ($r) => $r->withdraw_count > 0
                        ? round($r->withdraw_success_count / $r->withdraw_count * 100, 1) . '%'
                        : '-'),
            ]),
        ];
    }
}
