<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Bank;

use App\Enums\EntityStatus;
use App\Models\Bank;
use App\Orchid\Concerns\HasFilters;
use App\Orchid\Layouts\Shared\FilterPanel;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class BankListScreen extends Screen
{
    use HasFilters;

    public $permission = 'platform.banks';

    public function name(): ?string
    {
        return __('Banks');
    }

    public function description(): ?string
    {
        return __('Manage bank list');
    }

    public function query(Request $request): iterable
    {
        $filter = (array) $request->input('filter', []);

        $query = Bank::query()->defaultSort('sort_order');

        if (!empty($filter['bank_code'])) {
            $query->where('bank_code', 'like', "%{$filter['bank_code']}%");
        }
        if (!empty($filter['name'])) {
            $query->where('name', 'like', "%{$filter['name']}%");
        }
        if (isset($filter['status']) && $filter['status'] !== '') {
            $query->where('status', (int) $filter['status']);
        }

        return [
            'banks' => $query->paginate(),
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
        $filter = (array) request('filter', []);
        $summary = $this->buildFilterSummary($filter);

        return [
            FilterPanel::make(
                fields: [
                    Input::make('filter.bank_code')->title(__('Code'))->value($filter['bank_code'] ?? ''),
                    Input::make('filter.name')->title(__('Name'))->value($filter['name'] ?? ''),
                    Select::make('filter.status')->title(__('Status'))
                        ->empty(__('-- Any --'), '')
                        ->options(EntityStatus::options())
                        ->value($filter['status'] ?? ''),
                ],
                summary: $summary,
            ),

            Layout::table('banks', [
                TD::make('id', __('ID'))->sort(),
                TD::make('bank_code', __('Code'))->sort(),
                TD::make('name', __('Name'))->sort(),
                TD::make('status', __('Status'))
                    ->render(fn (Bank $b) => EntityStatus::tryFrom($b->status)?->label() ?? $b->status),
                TD::make('sort_order', __('Sort'))->sort(),
                TD::make(__('Actions'))
                    ->render(fn (Bank $b) => ModalToggle::make(__('Edit'))
                        ->icon('bs.pencil')
                        ->modal('editModal')
                        ->method('save')
                        ->asyncParameters(['bank' => $b->id])),
            ]),

            Layout::modal('createModal', Layout::rows([
                Input::make('bank.id')->type('hidden'),
                Input::make('bank.bank_code')->title(__('Code'))->required(),
                Input::make('bank.name')->title(__('Name'))->required(),
                Select::make('bank.status')->title(__('Status'))->options(EntityStatus::options())->required(),
                Input::make('bank.sort_order')->title(__('Sort Order'))->type('number')->value(0),
            ]))->title(__('Create Bank'))->applyButton(__('Save')),

            Layout::modal('editModal', Layout::rows([
                Input::make('bank.id')->type('hidden'),
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

    protected function filterRoute(): string
    {
        return 'platform.banks';
    }

    private function buildFilterSummary(array $f): array
    {
        $s = [];

        if (!empty($f['bank_code'])) {
            $s[__('Code')] = $f['bank_code'];
        }
        if (!empty($f['name'])) {
            $s[__('Name')] = $f['name'];
        }
        if (isset($f['status']) && $f['status'] !== '') {
            $s[__('Status')] = EntityStatus::tryFrom((int) $f['status'])?->label() ?? $f['status'];
        }

        return $s;
    }
}
