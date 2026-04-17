<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Merchant;

use App\Enums\EntityStatus;
use App\Models\Agent;
use App\Models\Merchant;
use App\Orchid\Concerns\HasFilters;
use App\Orchid\Layouts\Shared\FilterPanel;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class MerchantListScreen extends Screen
{
    use HasFilters;

    public $permission = 'platform.merchants';

    public function name(): ?string
    {
        return __('Merchants');
    }

    public function description(): ?string
    {
        return __('Manage merchant accounts');
    }

    public function query(Request $request): iterable
    {
        $filter = (array) $request->input('filter', []);

        $query = Merchant::with('agent')
            ->defaultSort('id', 'desc');

        if (!empty($filter['code'])) {
            $query->where('code', 'like', "%{$filter['code']}%");
        }
        if (!empty($filter['name'])) {
            $query->where('name', 'like', "%{$filter['name']}%");
        }
        if (!empty($filter['agent_id'])) {
            $query->where('agent_id', (int) $filter['agent_id']);
        }
        if (isset($filter['status']) && $filter['status'] !== '') {
            $query->where('status', (int) $filter['status']);
        }

        return [
            'merchants' => $query->paginate(),
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
        $filter = (array) request('filter', []);
        $summary = $this->buildFilterSummary($filter);

        return [
            FilterPanel::make(
                fields: [
                    Input::make('filter.code')->title(__('Code'))->value($filter['code'] ?? ''),
                    Input::make('filter.name')->title(__('Name'))->value($filter['name'] ?? ''),
                    Select::make('filter.agent_id')->title(__('Agent'))
                        ->empty(__('-- Any --'), '')
                        ->fromQuery(Agent::query()->where('types', 'merchant')->orderBy('name'), 'name', 'id')
                        ->value($filter['agent_id'] ?? ''),
                    Select::make('filter.status')->title(__('Status'))
                        ->empty(__('-- Any --'), '')
                        ->options(EntityStatus::options())
                        ->value($filter['status'] ?? ''),
                ],
                summary: $summary,
            ),

            Layout::table('merchants', [
                TD::make('id', __('ID'))->sort(),
                TD::make('code', __('Code'))->sort(),
                TD::make('name', __('Name'))->sort(),
                TD::make('agent_id', __('Agent'))
                    ->render(fn (Merchant $m) => $m->agent?->name ?? '-'),
                TD::make('status', __('Status'))
                    ->render(fn (Merchant $m) => EntityStatus::tryFrom($m->status)?->label() ?? $m->status),
                TD::make('available_balance', __('Balance'))->sort()->alignRight(),
                TD::make('created_at', __('Created'))->sort()
                    ->render(fn (Merchant $m) => $m->created_at?->format('Y-m-d H:i:s')),
                TD::make(__('Actions'))
                    ->render(fn (Merchant $m) => Link::make(__('Edit'))
                        ->route('platform.merchants.edit', $m)
                        ->icon('bs.pencil')),
            ]),
        ];
    }

    protected function filterRoute(): string
    {
        return 'platform.merchants';
    }

    private function buildFilterSummary(array $f): array
    {
        $s = [];

        if (!empty($f['code'])) {
            $s[__('Code')] = $f['code'];
        }
        if (!empty($f['name'])) {
            $s[__('Name')] = $f['name'];
        }
        if (!empty($f['agent_id'])) {
            $name = Agent::whereKey((int) $f['agent_id'])->value('name');
            $s[__('Agent')] = $name ?: $f['agent_id'];
        }
        if (isset($f['status']) && $f['status'] !== '') {
            $s[__('Status')] = EntityStatus::tryFrom((int) $f['status'])?->label() ?? $f['status'];
        }

        return $s;
    }
}
