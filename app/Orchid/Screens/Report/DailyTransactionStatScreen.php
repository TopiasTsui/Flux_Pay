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
        return __('Daily Transaction Statistics');
    }

    public function description(): ?string
    {
        return __('Transaction volume and success rates by day');
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
}
