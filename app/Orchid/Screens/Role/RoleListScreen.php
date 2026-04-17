<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Role;

use App\Orchid\Concerns\HasFilters;
use App\Orchid\Layouts\Role\RoleListLayout;
use App\Orchid\Layouts\Shared\FilterPanel;
use Illuminate\Http\Request;
use Orchid\Platform\Models\Role;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Screen;

class RoleListScreen extends Screen
{
    use HasFilters;

    public function query(Request $request): iterable
    {
        $filter = (array) $request->input('filter', []);

        $query = Role::query()->defaultSort('id', 'desc');

        if (!empty($filter['search'])) {
            $s = $filter['search'];
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                    ->orWhere('slug', 'like', "%{$s}%");
            });
        }

        return [
            'roles' => $query->paginate(),
        ];
    }

    public function name(): ?string
    {
        return __('Role Management');
    }

    public function description(): ?string
    {
        return __('A comprehensive list of all roles, including their permissions and associated users.');
    }

    public function permission(): ?iterable
    {
        return [
            'platform.systems.roles',
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Add'))
                ->icon('bs.plus-circle')
                ->href(route('platform.systems.roles.create')),
        ];
    }

    public function layout(): iterable
    {
        $filter = (array) request('filter', []);
        $summary = $this->buildFilterSummary($filter);

        return [
            FilterPanel::make(
                fields: [
                    Input::make('filter.search')->title(__('Search'))
                        ->placeholder(__('Name or slug'))
                        ->value($filter['search'] ?? ''),
                ],
                summary: $summary,
            ),

            RoleListLayout::class,
        ];
    }

    protected function filterRoute(): string
    {
        return 'platform.systems.roles';
    }

    private function buildFilterSummary(array $f): array
    {
        $s = [];

        if (!empty($f['search'])) {
            $s[__('Search')] = $f['search'];
        }

        return $s;
    }
}
