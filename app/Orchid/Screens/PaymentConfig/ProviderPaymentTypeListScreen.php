<?php

declare(strict_types=1);

namespace App\Orchid\Screens\PaymentConfig;

use App\Enums\EntityStatus;
use App\Models\Provider;
use App\Models\ProviderPaymentType;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class ProviderPaymentTypeListScreen extends Screen
{
    public $permission = 'platform.payment-config';

    public function name(): ?string
    {
        return __('Provider Payment Channels');
    }

    public function description(): ?string
    {
        return __('Manage provider payment type configurations');
    }

    public function query(): iterable
    {
        return [
            'channels' => ProviderPaymentType::with(['provider', 'paymentType'])
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
                ->route('platform.provider-payment-types.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('channels', [
                TD::make('id', __('ID'))->sort(),
                TD::make('provider_id', __('Provider'))
                    ->render(fn (ProviderPaymentType $c) => $c->provider?->name ?? '-'),
                TD::make('payment_type_id', __('Payment Type'))
                    ->render(fn (ProviderPaymentType $c) => $c->paymentType?->name ?? '-'),
                TD::make('type', __('Direction'))
                    ->render(fn (ProviderPaymentType $c) => \App\Enums\PaymentDirection::tryFrom($c->type)?->label()),
                TD::make('alias', __('Alias')),
                TD::make('status', __('Status'))
                    ->render(fn (ProviderPaymentType $c) => \App\Enums\EntityStatus::tryFrom($c->status)?->label() ?? $c->status)
                    ->filter(Select::make()->options(EntityStatus::options())->empty(__('All'))),
                TD::make('weight', __('Weight'))->sort(),
                TD::make(__('Actions'))
                    ->render(fn (ProviderPaymentType $c) => Link::make(__('Edit'))
                        ->route('platform.provider-payment-types.edit', $c)
                        ->icon('bs.pencil')),
            ]),
        ];
    }
}
