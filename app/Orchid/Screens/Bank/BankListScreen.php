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
        return __('Banks');
    }

    public function description(): ?string
    {
        return __('Manage bank list');
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
            ModalToggle::make(__('Create'))
                ->icon('bs.plus')
                ->modal('createModal')
                ->method('save'),
        ];
    }

    public function layout(): iterable
    {
        return [
            Layout::table('banks', [
                TD::make('id', __('ID'))->sort(),
                TD::make('bank_code', __('Code'))->sort()->filter(Input::make()),
                TD::make('name', __('Name'))->sort(),
                TD::make('status', __('Status'))
                    ->render(fn (Bank $b) => \App\Enums\EntityStatus::tryFrom($b->status)?->label() ?? $b->status),
                TD::make('sort_order', __('Sort'))->sort(),
                TD::make(__('Actions'))
                    ->render(fn (Bank $b) => ModalToggle::make(__('Edit'))
                        ->icon('bs.pencil')
                        ->modal('editModal')
                        ->method('save')
                        ->asyncParameters(['bank' => $b->id])),
            ]),

            Layout::modal('createModal', Layout::rows([
                Input::make('bank.bank_code')->title(__('Code'))->required(),
                Input::make('bank.name')->title(__('Name'))->required(),
                Select::make('bank.status')->title(__('Status'))->options(EntityStatus::options())->required(),
                Input::make('bank.sort_order')->title(__('Sort Order'))->type('number')->value(0),
            ]))->title(__('Create Bank'))->applyButton(__('Save')),

            Layout::modal('editModal', Layout::rows([
                Input::make('bank.bank_code')->title(__('Code'))->required(),
                Input::make('bank.name')->title(__('Name'))->required(),
                Select::make('bank.status')->title(__('Status'))->options(EntityStatus::options())->required(),
                Input::make('bank.sort_order')->title(__('Sort Order'))->type('number'),
            ]))->title(__('Edit Bank'))->applyButton(__('Save'))->async('asyncGetBank'),
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

        Toast::info(__('Bank saved.'));
    }
}
