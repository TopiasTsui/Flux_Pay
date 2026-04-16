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
        return 'Merchant Wallet Records';
    }

    public function description(): ?string
    {
        return 'View merchant wallet transaction history';
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
                TD::make('id', 'ID')->sort(),
                TD::make('merchant_id', 'Merchant')
                    ->render(fn (MerchantWalletRecord $r) => $r->merchant?->name ?? '-'),
                TD::make('sn', 'SN')->filter(Input::make()),
                TD::make('type_code', 'Type')
                    ->render(fn (MerchantWalletRecord $r) => $r->type_code?->label()),
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
