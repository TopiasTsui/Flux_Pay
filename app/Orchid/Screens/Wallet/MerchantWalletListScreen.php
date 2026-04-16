<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Wallet;

use App\Models\MerchantWalletRecord;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class MerchantWalletListScreen extends Screen
{
    public $permission = 'platform.wallets';

    public function name(): ?string
    {
        return __('Merchant Wallet Records');
    }

    public function description(): ?string
    {
        return __('View merchant wallet transaction history');
    }

    public function query(): iterable
    {
        return [
            'records' => MerchantWalletRecord::with('merchant')
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
                TD::make('merchant_id', __('Merchant'))
                    ->render(fn (MerchantWalletRecord $r) => $r->merchant?->name ?? '-'),
                TD::make('sn', __('SN'))->filter(Input::make()),
                TD::make('type_code', __('Type'))
                    ->render(fn (MerchantWalletRecord $r) => \App\Enums\WalletOperationType::tryFrom($r->type_code)?->label()),
                TD::make('amount', __('Amount'))->sort()->alignRight(),
                TD::make('pre_available_balance', __('Before'))->alignRight(),
                TD::make('available_balance', __('After'))->alignRight(),
                TD::make('system_order_no', __('Order No'))->filter(Input::make()),
                TD::make('remark', __('Remark')),
                TD::make('created_at', __('Created'))->sort()
                    ->render(fn (MerchantWalletRecord $r) => $r->created_at?->format('Y-m-d H:i:s')),
            ]),
        ];
    }
}
