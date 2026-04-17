<?php

declare(strict_types=1);

namespace App\Orchid\Screens\System;

use App\Models\SystemConfig;
use App\Orchid\Concerns\HasFilters;
use App\Orchid\Layouts\Shared\FilterPanel;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\TextArea;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class SystemConfigScreen extends Screen
{
    use HasFilters;

    public $permission = 'platform.system';

    public function name(): ?string
    {
        return __('System Configuration');
    }

    public function description(): ?string
    {
        return __('Manage system-wide configuration values');
    }

    public function query(Request $request): iterable
    {
        $filter = (array) $request->input('filter', []);

        $query = SystemConfig::query()->defaultSort('group');

        if (!empty($filter['group'])) {
            $query->where('group', $filter['group']);
        }
        if (!empty($filter['key'])) {
            $query->where('key', 'like', "%{$filter['key']}%");
        }

        return [
            'configs' => $query->paginate(),
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

        $groupOptions = SystemConfig::query()
            ->select('group')->distinct()
            ->orderBy('group')
            ->pluck('group', 'group')
            ->all();

        return [
            FilterPanel::make(
                fields: [
                    Select::make('filter.group')->title(__('Group'))
                        ->empty(__('-- Any --'), '')
                        ->options($groupOptions)
                        ->value($filter['group'] ?? ''),
                    Input::make('filter.key')->title(__('Key'))->value($filter['key'] ?? ''),
                ],
                summary: $summary,
            ),

            Layout::table('configs', [
                TD::make('id', __('ID'))->sort(),
                TD::make('group', __('Group'))->sort(),
                TD::make('key', __('Key'))->sort(),
                TD::make('value', __('Value'))
                    ->render(fn (SystemConfig $c) => mb_strlen($c->value) > 80
                        ? mb_substr($c->value, 0, 80) . '...'
                        : $c->value),
                TD::make('remark', __('Remark')),
                TD::make(__('Actions'))
                    ->render(fn (SystemConfig $c) => ModalToggle::make(__('Edit'))
                        ->icon('bs.pencil')
                        ->modal('editModal')
                        ->method('save')
                        ->asyncParameters(['config' => $c->id])),
            ]),

            Layout::modal('createModal', Layout::rows([
                Input::make('config.id')->type('hidden'),
                Input::make('config.group')->title(__('Group'))->value('general'),
                Input::make('config.key')->title(__('Key'))->required(),
                TextArea::make('config.value')->title(__('Value'))->required()->rows(3),
                Input::make('config.remark')->title(__('Remark')),
            ]))->title(__('Create Config'))->applyButton(__('Save')),

            Layout::modal('editModal', Layout::rows([
                Input::make('config.id')->type('hidden'),
                Input::make('config.group')->title(__('Group')),
                Input::make('config.key')->title(__('Key'))->required(),
                TextArea::make('config.value')->title(__('Value'))->required()->rows(3),
                Input::make('config.remark')->title(__('Remark')),
            ]))->title(__('Edit Config'))->applyButton(__('Save'))->async('asyncGetConfig'),
        ];
    }

    public function asyncGetConfig(SystemConfig $config): iterable
    {
        return [
            'config' => $config,
        ];
    }

    public function save(Request $request): void
    {
        $data = $request->validate([
            'config.group' => 'nullable|string|max:64',
            'config.key' => 'required|string|max:128',
            'config.value' => 'required|string',
            'config.remark' => 'nullable|string|max:255',
        ]);

        $id = $request->input('config.id');
        $config = $id ? SystemConfig::findOrFail($id) : new SystemConfig();
        $config->fill($data['config'])->save();

        Toast::info(__('Config saved.'));
    }

    protected function filterRoute(): string
    {
        return 'platform.system.configs';
    }

    private function buildFilterSummary(array $f): array
    {
        $s = [];

        if (!empty($f['group'])) {
            $s[__('Group')] = $f['group'];
        }
        if (!empty($f['key'])) {
            $s[__('Key')] = $f['key'];
        }

        return $s;
    }
}
