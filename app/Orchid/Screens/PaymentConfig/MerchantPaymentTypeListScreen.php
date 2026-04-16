<?php

declare(strict_types=1);

namespace App\Orchid\Screens\PaymentConfig;

use App\Models\MerchantPaymentType;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class MerchantPaymentTypeListScreen extends Screen
{
    public $permission = 'platform.payment-config';

    public function name(): ?string
    {
        return __('Merchant Payment Configs');
    }

    public function description(): ?string
    {
        return __('Manage merchant fee configurations per payment type');
    }

    public function query(): iterable
    {
        return [
            'configs' => MerchantPaymentType::with(['merchant', 'paymentType'])
                ->filters()
                ->defaultSort('id', 'desc')
                ->paginate(),
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Create'))
                ->icon('bs.plus')
                ->route('platform.merchant-payment-types.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('configs', [
                TD::make('id', __('ID'))->sort(),
                TD::make('merchant_id', __('Merchant'))
                    ->render(fn (MerchantPaymentType $c) => $c->merchant?->name ?? '-'),
                TD::make('payment_type_id', __('Payment Type'))
                    ->render(fn (MerchantPaymentType $c) => $c->paymentType?->name ?? '-'),
                TD::make('status', __('Status'))
                    ->render(fn (MerchantPaymentType $c) => \App\Enums\EntityStatus::tryFrom($c->status)?->label() ?? $c->status),
                TD::make('deposit_fee_type', __('Deposit Fee Type'))
                    ->render(fn (MerchantPaymentType $c) => \App\Enums\FeeType::tryFrom($c->deposit_fee_type)?->label() ?? '-'),
                TD::make('deposit_fee', __('Deposit Fee')),
                TD::make('withdraw_fee_type', __('Withdraw Fee Type'))
                    ->render(fn (MerchantPaymentType $c) => \App\Enums\FeeType::tryFrom($c->withdraw_fee_type)?->label() ?? '-'),
                TD::make('withdraw_fee', __('Withdraw Fee')),
                TD::make(__('Actions'))
                    ->render(fn (MerchantPaymentType $c) => Link::make(__('Edit'))
                        ->route('platform.merchant-payment-types.edit', $c)
                        ->icon('bs.pencil')),
            ]),
        ];
    }
}
