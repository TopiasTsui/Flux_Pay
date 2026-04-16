<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Provider;

use App\Enums\EntityStatus;
use App\Models\Provider;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class ProviderListScreen extends Screen
{
    public $permission = 'platform.providers';

    public function name(): ?string
    {
        return __('Providers');
    }

    public function description(): ?string
    {
        return __('Manage payment providers');
    }

    public function query(): iterable
    {
        return [
            'providers' => Provider::filters()
                ->defaultSort('id', 'desc')
                ->paginate(),
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Create'))
                ->icon('bs.plus')
                ->route('platform.providers.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('providers', [
                TD::make('id', __('ID'))->sort(),
                TD::make('name', __('Name'))->sort()->filter(Input::make()),
                TD::make('vendor_id', __('Vendor ID'))->sort()->filter(Input::make()),
                TD::make('status', __('Status'))
                    ->render(fn (Provider $p) => \App\Enums\EntityStatus::tryFrom($p->status)?->label() ?? $p->status)
                    ->filter(Select::make()->options(EntityStatus::options())->empty(__('All'))),
                TD::make('available_balance', __('Balance'))->sort()->alignRight(),
                TD::make(__('Actions'))
                    ->render(fn (Provider $p) => Link::make(__('Edit'))
                        ->route('platform.providers.edit', $p)
                        ->icon('bs.pencil')),
            ]),
        ];
    }
}
