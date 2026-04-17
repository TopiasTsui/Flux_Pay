<?php

declare(strict_types=1);

namespace App\Orchid\Screens\Provider;

use App\Enums\EntityStatus;
use App\Models\Provider;
use App\Orchid\Concerns\HasFilters;
use App\Orchid\Layouts\Shared\FilterPanel;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;

class ProviderListScreen extends Screen
{
    use HasFilters;

    public $permission = 'platform.providers';

    public function name(): ?string
    {
        return __('Providers');
    }

    public function description(): ?string
    {
        return __('Manage payment providers');
    }

    public function query(Request $request): iterable
    {
        $filter = (array) $request->input('filter', []);

        $query = Provider::query()->defaultSort('id', 'desc');

        if (!empty($filter['name'])) {
            $query->where('name', 'like', "%{$filter['name']}%");
        }
        if (!empty($filter['vendor_id'])) {
            $query->where('vendor_id', 'like', "%{$filter['vendor_id']}%");
        }
        if (isset($filter['status']) && $filter['status'] !== '') {
            $query->where('status', (int) $filter['status']);
        }

        return [
            'providers' => $query->paginate(),
        ];
    }

    public function commandBar(): iterable
    {
        return [
            Link::make(__('Create'))
                ->icon('bs.plus')
                ->route('platform.providers.create'),
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
                    Input::make('filter.vendor_id')->title(__('Vendor ID'))->value($filter['vendor_id'] ?? ''),
                    Select::make('filter.status')->title(__('Status'))
                        ->empty(__('-- Any --'), '')
                        ->options(EntityStatus::options())
                        ->value($filter['status'] ?? ''),
                ],
                summary: $summary,
            ),

            Layout::table('providers', [
                TD::make('id', __('ID'))->sort(),
                TD::make('name', __('Name'))->sort(),
                TD::make('vendor_id', __('Vendor ID'))->sort(),
                TD::make('status', __('Status'))
                    ->render(fn (Provider $p) => EntityStatus::tryFrom($p->status)?->label() ?? $p->status),
                TD::make('available_balance', __('Balance'))->sort()->alignRight(),
                TD::make(__('Actions'))
                    ->render(fn (Provider $p) => Link::make(__('Edit'))
                        ->route('platform.providers.edit', $p)
                        ->icon('bs.pencil')),
            ]),
        ];
    }

    protected function filterRoute(): string
    {
        return 'platform.providers';
    }

    private function buildFilterSummary(array $f): array
    {
        $s = [];

        if (!empty($f['name'])) {
            $s[__('Name')] = $f['name'];
        }
        if (!empty($f['vendor_id'])) {
            $s[__('Vendor ID')] = $f['vendor_id'];
        }
        if (isset($f['status']) && $f['status'] !== '') {
            $s[__('Status')] = EntityStatus::tryFrom((int) $f['status'])?->label() ?? $f['status'];
        }

        return $s;
    }
}
