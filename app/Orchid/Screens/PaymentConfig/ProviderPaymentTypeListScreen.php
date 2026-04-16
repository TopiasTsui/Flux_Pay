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
        return 'Provider Payment Channels';
    }

    public function description(): ?string
    {
        return 'Manage provider payment type configurations';
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
            Link::make('Create')
                ->icon('bs.plus')
                ->route('platform.provider-payment-types.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('channels', [
                TD::make('id', 'ID')->sort(),
                TD::make('provider_id', 'Provider')
                    ->render(fn (ProviderPaymentType $c) => $c->provider?->name ?? '-'),
                TD::make('payment_type_id', 'Payment Type')
                    ->render(fn (ProviderPaymentType $c) => $c->paymentType?->name ?? '-'),
                TD::make('type', 'Direction')
                    ->render(fn (ProviderPaymentType $c) => $c->type?->label()),
                TD::make('alias', 'Alias'),
                TD::make('status', 'Status')
                    ->render(fn (ProviderPaymentType $c) => $c->status->label())
                    ->filter(Select::make()->options(EntityStatus::options())->empty('All')),
                TD::make('weight', 'Weight')->sort(),
                TD::make('actions', 'Actions')
                    ->render(fn (ProviderPaymentType $c) => Link::make('Edit')
                        ->route('platform.provider-payment-types.edit', $c)
                        ->icon('bs.pencil')),
            ]),
        ];
    }
}
