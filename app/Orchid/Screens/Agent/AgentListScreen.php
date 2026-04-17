<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Agent;

use App\Enums\AgentType;
use App\Enums\EntityStatus;
use App\Models\Agent;
use App\Orchid\Concerns\HasFilters;
use App\Orchid\Layouts\Shared\FilterPanel;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class AgentListScreen extends Screen
{
    use HasFilters;

    public $permission = 'platform.agents';

    public function name(): ?string
    {
        return __('Agents');
    }

    public function description(): ?string
    {
        return __('Manage agent accounts');
    }

    public function query(Request $request): iterable
    {
        $filter = (array) $request->input('filter', []);

        $query = Agent::with('parent')
            ->orderBy('parent_id')
            ->orderBy('level')
            ->orderBy('id');

        if (!empty($filter['name'])) {
            $query->where('name', 'like', "%{$filter['name']}%");
        }
        if (!empty($filter['types'])) {
            $query->where('types', $filter['types']);
        }
        if (!empty($filter['level'])) {
            $query->where('level', (int) $filter['level']);
        }
        if (!empty($filter['parent_id'])) {
            $query->where('parent_id', (int) $filter['parent_id']);
        }
        if (isset($filter['status']) && $filter['status'] !== '') {
            $query->where('status', (int) $filter['status']);
        }

        return [
            'agents' => $query->paginate(),
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Create'))
                ->icon('bs.plus')
                ->route('platform.agents.create'),
        ];
    }

    public function layout(): iterable
    {
        $filter = (array) request('filter', []);
        $summary = $this->buildFilterSummary($filter);

        return [
            FilterPanel::make(
                fields: [
                    Input::make('filter.name')->title(__('Name'))->value($filter['name'] ?? ''),
                    Select::make('filter.types')->title(__('Type'))
                        ->empty(__('-- Any --'), '')
                        ->options(AgentType::options())
                        ->value($filter['types'] ?? ''),
                    Select::make('filter.level')->title(__('Level'))
                        ->empty(__('-- Any --'), '')
                        ->options([1 => 'L1', 2 => 'L2', 3 => 'L3'])
                        ->value($filter['level'] ?? ''),
                    Select::make('filter.parent_id')->title(__('Parent'))
                        ->empty(__('-- Any --'), '')
                        ->fromQuery(Agent::query()->orderBy('name'), 'name', 'id')
                        ->value($filter['parent_id'] ?? ''),
                    Select::make('filter.status')->title(__('Status'))
                        ->empty(__('-- Any --'), '')
                        ->options(EntityStatus::options())
                        ->value($filter['status'] ?? ''),
                ],
                summary: $summary,
            ),

            Layout::table('agents', [
                TD::make('id', __('ID'))->sort(),
                TD::make('name', __('Name'))->sort()
                    ->render(fn (Agent $a) => str_repeat('— ', max(0, $a->level - 1)) . $a->name),
                TD::make('types', __('Type'))->render(fn (Agent $a) => AgentType::tryFrom($a->types)?->label() ?? $a->types),
                TD::make('level', __('Level'))->sort(),
                TD::make('status', __('Status'))
                    ->render(fn (Agent $a) => EntityStatus::tryFrom($a->status)?->label() ?? $a->status),
                TD::make('available_balance', __('Balance'))->sort()->alignRight(),
                TD::make('parent_id', __('Parent'))
                    ->render(fn (Agent $a) => $a->parent?->name ?? '-'),
                TD::make('created_at', __('Created'))->sort()->defaultHidden()
                    ->render(fn (Agent $a) => $a->created_at?->format('Y-m-d H:i:s')),
                TD::make(__('Actions'))
                    ->render(fn (Agent $a) => Link::make(__('Edit'))
                        ->route('platform.agents.edit', $a)
                        ->icon('bs.pencil')),
            ]),
        ];
    }

    protected function filterRoute(): string
    {
        return 'platform.agents';
    }

    private function buildFilterSummary(array $f): array
    {
        $s = [];

        if (!empty($f['name'])) {
            $s[__('Name')] = $f['name'];
        }
        if (!empty($f['types'])) {
            $s[__('Type')] = AgentType::tryFrom($f['types'])?->label() ?? $f['types'];
        }
        if (!empty($f['level'])) {
            $s[__('Level')] = 'L' . $f['level'];
        }
        if (!empty($f['parent_id'])) {
            $name = Agent::whereKey((int) $f['parent_id'])->value('name');
            $s[__('Parent')] = $name ?: $f['parent_id'];
        }
        if (isset($f['status']) && $f['status'] !== '') {
            $s[__('Status')] = EntityStatus::tryFrom((int) $f['status'])?->label() ?? $f['status'];
        }

        return $s;
    }
}
