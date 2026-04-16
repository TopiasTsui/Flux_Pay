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
        return 'Blacklist';
    }

    public function description(): ?string
    {
        return 'Manage blacklisted entries';
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
            ModalToggle::make('Create')
                ->icon('bs.plus')
                ->modal('createModal')
                ->method('save'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('items', [
                TD::make('id', 'ID')->sort(),
                TD::make('type', 'Type')->sort()->filter(Input::make()),
                TD::make('value', 'Value')->filter(Input::make()),
                TD::make('remark', 'Remark'),
                TD::make('status', 'Status')
                    ->render(fn (Blacklist $b) => $b->status->label()),
                TD::make('created_at', 'Created')->sort(),
                TD::make('actions', 'Actions')
                    ->render(fn (Blacklist $b) => ModalToggle::make('Edit')
                        ->icon('bs.pencil')
                        ->modal('editModal')
                        ->method('save')
                        ->asyncParameters(['item' => $b->id])),
            ]),

            Layout::modal('createModal', Layout::rows([
                Input::make('item.type')->title('Type')->required()->help('e.g. ip, card, name'),
                Input::make('item.value')->title('Value')->required(),
                Input::make('item.remark')->title('Remark'),
                Select::make('item.status')->title('Status')->options(EntityStatus::options())->required(),
            ]))->title('Create Blacklist Entry')->applyButton('Save'),

            Layout::modal('editModal', Layout::rows([
                Input::make('item.type')->title('Type')->required(),
                Input::make('item.value')->title('Value')->required(),
                Input::make('item.remark')->title('Remark'),
                Select::make('item.status')->title('Status')->options(EntityStatus::options())->required(),
            ]))->title('Edit Blacklist Entry')->applyButton('Save')->async('asyncGetItem'),
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

        Toast::info('Blacklist entry saved.');
    }
}
