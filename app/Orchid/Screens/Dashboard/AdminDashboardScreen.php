<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Dashboard;

use App\Enums\OrderStatus;
use App\Models\Agent;
use App\Models\DepositOrder;
use App\Models\Merchant;
use App\Models\Provider;
use App\Models\WithdrawOrder;
use Orchid\Screen\Screen;
use Orchid\Support\Facades\Layout;

class AdminDashboardScreen extends Screen
{
    public function name(): ?string
    {
        return 'Dashboard';
    }

    public function description(): ?string
    {
        return 'FluxPay System Overview';
    }

    public function query(): iterable
    {
        $today = now()->toDateString();

        $depositTotal = DepositOrder::whereDate('created_at', $today)->count();
        $depositSuccess = DepositOrder::whereDate('created_at', $today)->where('status', OrderStatus::SUCCESS)->count();
        $depositAmount = DepositOrder::whereDate('created_at', $today)->where('status', OrderStatus::SUCCESS)->sum('actual_amount');
        $depositRate = $depositTotal > 0 ? round($depositSuccess / $depositTotal * 100, 1) : 0;

        $withdrawTotal = WithdrawOrder::whereDate('created_at', $today)->count();
        $withdrawSuccess = WithdrawOrder::whereDate('created_at', $today)->where('status', OrderStatus::SUCCESS)->count();
        $withdrawAmount = WithdrawOrder::whereDate('created_at', $today)->where('status', OrderStatus::SUCCESS)->sum('actual_amount');
        $withdrawRate = $withdrawTotal > 0 ? round($withdrawSuccess / $withdrawTotal * 100, 1) : 0;

        return [
            'metrics' => [
                'deposit_count' => ['value' => number_format($depositTotal)],
                'deposit_amount' => ['value' => number_format((float) $depositAmount, 2)],
                'deposit_rate' => ['value' => $depositRate . '%'],
                'withdraw_count' => ['value' => number_format($withdrawTotal)],
                'withdraw_amount' => ['value' => number_format((float) $withdrawAmount, 2)],
                'withdraw_rate' => ['value' => $withdrawRate . '%'],
                'merchants' => ['value' => number_format(Merchant::count())],
                'agents' => ['value' => number_format(Agent::count())],
                'providers' => ['value' => number_format(Provider::count())],
            ],
        ];
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::metrics([
                'Today Deposits' => 'metrics.deposit_count',
                'Deposit Amount' => 'metrics.deposit_amount',
                'Deposit Success Rate' => 'metrics.deposit_rate',
            ]),
            Layout::metrics([
                'Today Withdrawals' => 'metrics.withdraw_count',
                'Withdrawal Amount' => 'metrics.withdraw_amount',
                'Withdrawal Success Rate' => 'metrics.withdraw_rate',
            ]),
            Layout::metrics([
                'Total Merchants' => 'metrics.merchants',
                'Total Agents' => 'metrics.agents',
                'Total Providers' => 'metrics.providers',
            ]),
        ];
    }
}
