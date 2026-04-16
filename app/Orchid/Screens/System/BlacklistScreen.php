<?php

declare(strict_types=1);

namespace App\Orchid\Screens\System;

use App\Enums\EntityStatus;
use App\Models\Blacklist;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class BlacklistScreen extends Screen
{
    public $permission = 'platform.system';

    public function name(): ?string
    {
        return __('Blacklist');
    }

    public function description(): ?string
    {
        return __('Manage blacklisted entries');
    }

    public function query(): iterable
    {
        return [
            'items' => Blacklist::filters()->defaultSort('id', 'desc')->paginate(),
        ];
    }

    public function commandBar(): iterable
    {
        return [
            ModalToggle::make(__('Create'))
                ->icon('bs.plus')
                ->modal('createModal')
                ->method('save'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('items', [
                TD::make('id', __('ID'))->sort(),
                TD::make('type', __('Type'))->sort()->filter(Input::make()),
                TD::make('value', __('Value'))->filter(Input::make()),
                TD::make('remark', __('Remark')),
                TD::make('status', __('Status'))
                    ->render(fn (Blacklist $b) => \App\Enums\EntityStatus::tryFrom($b->status)?->label() ?? $b->status),
                TD::make('created_at', __('Created'))->sort(),
                TD::make(__('Actions'))
                    ->render(fn (Blacklist $b) => ModalToggle::make(__('Edit'))
                        ->icon('bs.pencil')
                        ->modal('editModal')
                        ->method('save')
                        ->asyncParameters(['item' => $b->id])),
            ]),

            Layout::modal('createModal', Layout::rows([
                Input::make('item.type')->title(__('Type'))->required()->help(__('e.g. ip, card, name')),
                Input::make('item.value')->title(__('Value'))->required(),
                Input::make('item.remark')->title(__('Remark')),
                Select::make('item.status')->title(__('Status'))->options(EntityStatus::options())->required(),
            ]))->title(__('Create Blacklist Entry'))->applyButton(__('Save')),

            Layout::modal('editModal', Layout::rows([
                Input::make('item.type')->title(__('Type'))->required(),
                Input::make('item.value')->title(__('Value'))->required(),
                Input::make('item.remark')->title(__('Remark')),
                Select::make('item.status')->title(__('Status'))->options(EntityStatus::options())->required(),
            ]))->title(__('Edit Blacklist Entry'))->applyButton(__('Save'))->async('asyncGetItem'),
        ];
    }

    public function asyncGetItem(Blacklist $item): iterable
    {
        return [
            'item' => $item,
        ];
    }

    public function save(Request $request): void
    {
        $data = $request->validate([
            'item.type' => 'required|string|max:32',
            'item.value' => 'required|string|max:255',
            'item.remark' => 'nullable|string|max:255',
            'item.status' => 'required',
        ]);

        $id = $request->input('item.id');
        $item = $id ? Blacklist::findOrFail($id) : new Blacklist();
        $item->fill($data['item'])->save();

        Toast::info(__('Blacklist entry saved.'));
    }
}
