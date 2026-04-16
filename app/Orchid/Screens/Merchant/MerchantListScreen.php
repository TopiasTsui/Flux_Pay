<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Merchant;

use App\Enums\EntityStatus;
use App\Models\Merchant;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class MerchantListScreen extends Screen
{
    public $permission = 'platform.merchants';

    public function name(): ?string
    {
        return __('Merchants');
    }

    public function description(): ?string
    {
        return __('Manage merchant accounts');
    }

    public function query(): iterable
    {
        return [
            'merchants' => Merchant::with('agent')
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
                ->route('platform.merchants.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('merchants', [
                TD::make('id', __('ID'))->sort(),
                TD::make('code', __('Code'))->sort()->filter(Input::make()),
                TD::make('name', __('Name'))->sort()->filter(Input::make()),
                TD::make('agent_id', __('Agent'))
                    ->render(fn (Merchant $m) => $m->agent?->name ?? '-'),
                TD::make('status', __('Status'))
                    ->render(fn (Merchant $m) => \App\Enums\EntityStatus::tryFrom($m->status)?->label() ?? $m->status)
                    ->filter(Select::make()->options(EntityStatus::options())->empty(__('All'))),
                TD::make('available_balance', __('Balance'))->sort()->alignRight(),
                TD::make('created_at', __('Created'))->sort(),
                TD::make(__('Actions'))
                    ->render(fn (Merchant $m) => Link::make(__('Edit'))
                        ->route('platform.merchants.edit', $m)
                        ->icon('bs.pencil')),
            ]),
        ];
    }
}
