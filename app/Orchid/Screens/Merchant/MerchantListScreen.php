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
        return 'Merchants';
    }

    public function description(): ?string
    {
        return 'Manage merchant accounts';
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
            Link::make('Create')
                ->icon('bs.plus')
                ->route('platform.merchants.create'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('merchants', [
                TD::make('id', 'ID')->sort(),
                TD::make('code', 'Code')->sort()->filter(Input::make()),
                TD::make('name', 'Name')->sort()->filter(Input::make()),
                TD::make('agent_id', 'Agent')
                    ->render(fn (Merchant $m) => $m->agent?->name ?? '-'),
                TD::make('status', 'Status')
                    ->render(fn (Merchant $m) => $m->status->label())
                    ->filter(Select::make()->options(EntityStatus::options())->empty('All')),
                TD::make('available_balance', 'Balance')->sort()->alignRight(),
                TD::make('created_at', 'Created')->sort(),
                TD::make('actions', 'Actions')
                    ->render(fn (Merchant $m) => Link::make('Edit')
                        ->route('platform.merchants.edit', $m)
                        ->icon('bs.pencil')),
            ]),
        ];
    }
}
