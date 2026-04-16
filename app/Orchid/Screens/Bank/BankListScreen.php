<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Bank;

use App\Enums\EntityStatus;
use App\Models\Bank;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class BankListScreen extends Screen
{
    public $permission = 'platform.banks';

    public function name(): ?string
    {
        return 'Banks';
    }

    public function description(): ?string
    {
        return 'Manage bank list';
    }

    public function query(): iterable
    {
        return [
            'banks' => Bank::filters()->defaultSort('sort_order')->paginate(),
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
            Layout::table('banks', [
                TD::make('id', 'ID')->sort(),
                TD::make('bank_code', 'Code')->sort()->filter(Input::make()),
                TD::make('name', 'Name')->sort(),
                TD::make('status', 'Status')
                    ->render(fn (Bank $b) => \App\Enums\EntityStatus::tryFrom($b->status)?->label() ?? $b->status),
                TD::make('sort_order', 'Sort')->sort(),
                TD::make('actions', 'Actions')
                    ->render(fn (Bank $b) => ModalToggle::make('Edit')
                        ->icon('bs.pencil')
                        ->modal('editModal')
                        ->method('save')
                        ->asyncParameters(['bank' => $b->id])),
            ]),

            Layout::modal('createModal', Layout::rows([
                Input::make('bank.bank_code')->title('Code')->required(),
                Input::make('bank.name')->title('Name')->required(),
                Select::make('bank.status')->title('Status')->options(EntityStatus::options())->required(),
                Input::make('bank.sort_order')->title('Sort Order')->type('number')->value(0),
            ]))->title('Create Bank')->applyButton('Save'),

            Layout::modal('editModal', Layout::rows([
                Input::make('bank.bank_code')->title('Code')->required(),
                Input::make('bank.name')->title('Name')->required(),
                Select::make('bank.status')->title('Status')->options(EntityStatus::options())->required(),
                Input::make('bank.sort_order')->title('Sort Order')->type('number'),
            ]))->title('Edit Bank')->applyButton('Save')->async('asyncGetBank'),
        ];
    }

    public function asyncGetBank(Bank $bank): iterable
    {
        return [
            'bank' => $bank,
        ];
    }

    public function save(Request $request): void
    {
        $data = $request->validate([
            'bank.bank_code' => 'required|string|max:32',
            'bank.name' => 'required|string|max:255',
            'bank.status' => 'required',
            'bank.sort_order' => 'nullable|integer',
        ]);

        $id = $request->input('bank.id');
        $bank = $id ? Bank::findOrFail($id) : new Bank();
        $bank->fill($data['bank'])->save();

        Toast::info('Bank saved.');
    }
}
