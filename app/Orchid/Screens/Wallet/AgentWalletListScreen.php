<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Wallet;

use App\Models\AgentWalletRecord;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class AgentWalletListScreen extends Screen
{
    public $permission = 'platform.wallets';

    public function name(): ?string
    {
        return 'Agent Wallet Records';
    }

    public function description(): ?string
    {
        return 'View agent wallet transaction history';
    }

    public function query(): iterable
    {
        return [
            'records' => AgentWalletRecord::with('agent')
                ->filters()
                ->orderBy('created_at', 'desc')
                ->paginate(),
        ];
    }

    public function commandBar(): iterable
    {
        return [];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('records', [
                TD::make('id', 'ID')->sort(),
                TD::make('agent_id', 'Agent')
                    ->render(fn (AgentWalletRecord $r) => $r->agent?->name ?? '-'),
                TD::make('sn', 'SN')->filter(Input::make()),
                TD::make('type_code', 'Type')
                    ->render(fn (AgentWalletRecord $r) => \App\Enums\WalletOperationType::tryFrom($r->type_code)?->label()),
                TD::make('amount', 'Amount')->sort()->alignRight(),
                TD::make('pre_available_balance', 'Before')->alignRight(),
                TD::make('available_balance', 'After')->alignRight(),
                TD::make('system_order_no', 'Order No')->filter(Input::make()),
                TD::make('remark', 'Remark'),
                TD::make('created_at', 'Created')->sort(),
            ]),
        ];
    }
}
