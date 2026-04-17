<?php

declare(strict_types=1);

namespace App\Orchid\Screens\System;

use App\Enums\EntityStatus;
use App\Models\Blacklist;
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

class BlacklistScreen extends Screen
{
    use HasFilters;

    public $permission = 'platform.system';

    public function name(): ?string
    {
        return __('Blacklist');
    }

    public function description(): ?string
    {
        return __('Manage blacklisted entries');
    }

    public function query(Request $request): iterable
    {
        $filter = (array) $request->input('filter', []);

        $query = Blacklist::query()->defaultSort('id', 'desc');

        if (!empty($filter['type'])) {
            $query->where('type', $filter['type']);
        }
        if (!empty($filter['value'])) {
            $query->where('value', 'like', "%{$filter['value']}%");
        }
        if (isset($filter['status']) && $filter['status'] !== '') {
            $query->where('status', (int) $filter['status']);
        }

        return [
            'items' => $query->paginate(),
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

        $typeOptions = Blacklist::query()
            ->select('type')->distinct()
            ->orderBy('type')
            ->pluck('type', 'type')
            ->all();

        return [
            FilterPanel::make(
                fields: [
                    Select::make('filter.type')->title(__('Type'))
                        ->empty(__('-- Any --'), '')
                        ->options($typeOptions)
                        ->value($filter['type'] ?? ''),
                    Input::make('filter.value')->title(__('Value'))->value($filter['value'] ?? ''),
                    Select::make('filter.status')->title(__('Status'))
                        ->empty(__('-- Any --'), '')
                        ->options(EntityStatus::options())
                        ->value($filter['status'] ?? ''),
                ],
                summary: $summary,
            ),

            Layout::table('items', [
                TD::make('id', __('ID'))->sort(),
                TD::make('type', __('Type'))->sort(),
                TD::make('value', __('Value')),
                TD::make('remark', __('Remark')),
                TD::make('status', __('Status'))
                    ->render(fn (Blacklist $b) => EntityStatus::tryFrom($b->status)?->label() ?? $b->status),
                TD::make('created_at', __('Created'))->sort()
                    ->render(fn (Blacklist $b) => $b->created_at?->format('Y-m-d H:i:s')),
                TD::make(__('Actions'))
                    ->render(fn (Blacklist $b) => ModalToggle::make(__('Edit'))
                        ->icon('bs.pencil')
                        ->modal('editModal')
                        ->method('save')
                        ->asyncParameters(['item' => $b->id])),
            ]),

            Layout::modal('createModal', Layout::rows([
                Input::make('item.id')->type('hidden'),
                Input::make('item.type')->title(__('Type'))->required()->help(__('e.g. ip, card, name')),
                Input::make('item.value')->title(__('Value'))->required(),
                Input::make('item.remark')->title(__('Remark')),
                Select::make('item.status')->title(__('Status'))->options(EntityStatus::options())->required(),
            ]))->title(__('Create Blacklist Entry'))->applyButton(__('Save')),

            Layout::modal('editModal', Layout::rows([
                Input::make('item.id')->type('hidden'),
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

    protected function filterRoute(): string
    {
        return 'platform.system.blacklist';
    }

    private function buildFilterSummary(array $f): array
    {
        $s = [];

        if (!empty($f['type'])) {
            $s[__('Type')] = $f['type'];
        }
        if (!empty($f['value'])) {
            $s[__('Value')] = $f['value'];
        }
        if (isset($f['status']) && $f['status'] !== '') {
            $s[__('Status')] = EntityStatus::tryFrom((int) $f['status'])?->label() ?? $f['status'];
        }

        return $s;
    }
}
