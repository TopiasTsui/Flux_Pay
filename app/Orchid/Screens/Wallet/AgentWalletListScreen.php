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
        return __('Agent Wallet Records');
    }

    public function description(): ?string
    {
        return __('View agent wallet transaction history');
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
                TD::make('id', __('ID'))->sort(),
                TD::make('agent_id', __('Agent'))
                    ->render(fn (AgentWalletRecord $r) => $r->agent?->name ?? '-'),
                TD::make('sn', __('SN'))->filter(Input::make()),
                TD::make('type_code', __('Type'))
                    ->render(fn (AgentWalletRecord $r) => \App\Enums\WalletOperationType::tryFrom($r->type_code)?->label()),
                TD::make('amount', __('Amount'))->sort()->alignRight(),
                TD::make('pre_available_balance', __('Before'))->alignRight(),
                TD::make('available_balance', __('After'))->alignRight(),
                TD::make('system_order_no', __('Order No'))->filter(Input::make()),
                TD::make('remark', __('Remark')),
                TD::make('created_at', __('Created'))->sort(),
            ]),
        ];
    }
}
