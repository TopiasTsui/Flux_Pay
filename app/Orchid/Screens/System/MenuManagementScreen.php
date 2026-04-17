<?php

declare(strict_types=1);

namespace App\Orchid\Screens\System;

use App\Models\AdminMenu;
use App\Orchid\Concerns\HasFilters;
use App\Orchid\Layouts\Shared\FilterPanel;
use Illuminate\Http\Request;
use Orchid\Screen\Actions\Button;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\Fields\Input;
use Orchid\Screen\Fields\Select;
use Orchid\Screen\Fields\Switcher;
use Orchid\Screen\Screen;
use Orchid\Screen\TD;
use Orchid\Support\Facades\Layout;
use Orchid\Support\Facades\Toast;

class MenuManagementScreen extends Screen
{
    use HasFilters;

    public $permission = 'platform.system';

    public function name(): ?string
    {
        return __('Menu Management');
    }

    public function description(): ?string
    {
        return __('Manage admin sidebar menu items');
    }

    public function query(Request $request): iterable
    {
        $filter = (array) $request->input('filter', []);

        $query = AdminMenu::with('parent')
            ->orderBy('parent_id')
            ->orderBy('sort_order');

        if (!empty($filter['search'])) {
            $s = $filter['search'];
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                    ->orWhere('slug', 'like', "%{$s}%")
                    ->orWhere('route', 'like', "%{$s}%");
            });
        }
        if (!empty($filter['parent_id'])) {
            $query->where('parent_id', (int) $filter['parent_id']);
        }
        if (isset($filter['is_active']) && $filter['is_active'] !== '') {
            $query->where('is_active', (bool) $filter['is_active']);
        }

        return [
            'menus' => $query->paginate(50),
        ];
    }

    public function commandBar(): iterable
    {
        return [
            ModalToggle::make(__('Create'))
                ->icon('bs.plus')
                ->modal('createModal')
                ->method('save'),

            Button::make(__('Sync Missing'))
                ->icon('bs.plus-square')
                ->method('syncMissing')
                ->confirm(__('Add any default menu items missing by slug, keep existing ones intact?')),

            Button::make(__('Initialize Default'))
                ->icon('bs.arrow-repeat')
                ->method('initDefaults')
                ->confirm(__('This will ERASE all menu items and recreate defaults. Continue?')),
        ];
    }

    private function defaultStructure(): array
    {
        return [
            ['title' => 'Dashboard', 'slug' => 'dashboard', 'icon' => 'bs.speedometer2', 'route' => 'platform.dashboard', 'sort_order' => 1],
            ['title' => 'Payment Management', 'slug' => 'payment-management', 'icon' => 'bs.credit-card-2-front', 'permission' => 'platform.orders', 'sort_order' => 10,
                'children' => [
                    ['title' => 'Deposit Orders', 'slug' => 'deposit-orders', 'icon' => 'bs.arrow-down-circle', 'route' => 'platform.deposit-orders', 'sort_order' => 1],
                    ['title' => 'Withdraw Orders', 'slug' => 'withdraw-orders', 'icon' => 'bs.arrow-up-circle', 'route' => 'platform.withdraw-orders', 'sort_order' => 2],
                ],
            ],
            ['title' => 'Entity Management', 'slug' => 'entity-management', 'icon' => 'bs.building', 'sort_order' => 20,
                'children' => [
                    ['title' => 'Merchants', 'slug' => 'merchants', 'icon' => 'bs.shop', 'route' => 'platform.merchants', 'permission' => 'platform.merchants', 'sort_order' => 1],
                    ['title' => 'Agents', 'slug' => 'agents', 'icon' => 'bs.people', 'route' => 'platform.agents', 'permission' => 'platform.agents', 'sort_order' => 2],
                    ['title' => 'Providers', 'slug' => 'providers', 'icon' => 'bs.plug', 'route' => 'platform.providers', 'permission' => 'platform.providers', 'sort_order' => 3],
                ],
            ],
            ['title' => 'Payment Config', 'slug' => 'payment-config', 'icon' => 'bs.sliders', 'permission' => 'platform.payment-config', 'sort_order' => 30,
                'children' => [
                    ['title' => 'Payment Types', 'slug' => 'payment-types', 'icon' => 'bs.credit-card', 'route' => 'platform.payment-types', 'sort_order' => 1],
                    ['title' => 'Provider Channels', 'slug' => 'provider-channels', 'icon' => 'bs.diagram-3', 'route' => 'platform.provider-payment-types', 'sort_order' => 2],
                    ['title' => 'Merchant Fee Config', 'slug' => 'merchant-fee-config', 'icon' => 'bs.calculator', 'route' => 'platform.merchant-payment-types', 'sort_order' => 3],
                ],
            ],
            ['title' => 'Finance', 'slug' => 'finance', 'icon' => 'bs.wallet2', 'permission' => 'platform.wallets', 'sort_order' => 40,
                'children' => [
                    ['title' => 'Merchant Wallet', 'slug' => 'merchant-wallet', 'icon' => 'bs.wallet2', 'route' => 'platform.wallets.merchant', 'sort_order' => 1],
                    ['title' => 'Agent Wallet', 'slug' => 'agent-wallet', 'icon' => 'bs.wallet', 'route' => 'platform.wallets.agent', 'sort_order' => 2],
                    ['title' => 'Provider Wallet', 'slug' => 'provider-wallet', 'icon' => 'bs.wallet-fill', 'route' => 'platform.wallets.provider', 'sort_order' => 3],
                ],
            ],
            ['title' => 'Reports', 'slug' => 'reports', 'icon' => 'bs.graph-up', 'permission' => 'platform.reports', 'sort_order' => 50,
                'children' => [
                    ['title' => 'Transaction Stats', 'slug' => 'transaction-stats', 'icon' => 'bs.graph-up', 'route' => 'platform.reports.transactions', 'sort_order' => 1],
                    ['title' => 'Revenue Stats', 'slug' => 'revenue-stats', 'icon' => 'bs.currency-dollar', 'route' => 'platform.reports.revenue', 'sort_order' => 2],
                ],
            ],
            ['title' => 'Banks', 'slug' => 'banks-group', 'icon' => 'bs.bank', 'permission' => 'platform.banks', 'sort_order' => 60,
                'children' => [
                    ['title' => 'Bank List', 'slug' => 'bank-list', 'icon' => 'bs.bank', 'route' => 'platform.banks', 'sort_order' => 1],
                    ['title' => 'Provider Bank Codes', 'slug' => 'provider-bank-codes', 'icon' => 'bs.link-45deg', 'route' => 'platform.provider-bank-codes', 'sort_order' => 2],
                ],
            ],
            ['title' => 'System', 'slug' => 'system-group', 'icon' => 'bs.gear', 'permission' => 'platform.system', 'sort_order' => 70,
                'children' => [
                    ['title' => 'System Config', 'slug' => 'system-config', 'icon' => 'bs.gear', 'route' => 'platform.system.configs', 'sort_order' => 1],
                    ['title' => 'Blacklist', 'slug' => 'blacklist', 'icon' => 'bs.shield-x', 'route' => 'platform.system.blacklist', 'sort_order' => 2],
                    ['title' => 'Menu Management', 'slug' => 'menu-management', 'icon' => 'bs.list', 'route' => 'platform.system.menus', 'sort_order' => 3],
                    ['title' => 'Locales', 'slug' => 'locales', 'icon' => 'bs.translate', 'route' => 'platform.system.i18n.locales', 'permission' => 'platform.system.i18n', 'sort_order' => 4],
                    ['title' => 'Translations', 'slug' => 'translations', 'icon' => 'bs.chat-left-text', 'route' => 'platform.system.i18n.translations', 'permission' => 'platform.system.i18n', 'sort_order' => 5],
                ],
            ],
            ['title' => 'Access Controls', 'slug' => 'access-controls', 'icon' => 'bs.lock', 'sort_order' => 80,
                'children' => [
                    ['title' => 'Users', 'slug' => 'users', 'icon' => 'bs.people', 'route' => 'platform.systems.users', 'permission' => 'platform.systems.users', 'sort_order' => 1],
                    ['title' => 'Roles', 'slug' => 'roles', 'icon' => 'bs.shield', 'route' => 'platform.systems.roles', 'permission' => 'platform.systems.roles', 'sort_order' => 2],
                ],
            ],
        ];
    }

    public function syncMissing(): void
    {
        $inserted = 0;

        foreach ($this->defaultStructure() as $item) {
            $children = $item['children'] ?? [];
            unset($item['children']);

            $parent = AdminMenu::where('slug', $item['slug'])->first();

            if (! $parent) {
                $item['is_active'] = true;
                $parent = AdminMenu::create($item);
                $inserted++;
            }

            foreach ($children as $child) {
                if (AdminMenu::where('slug', $child['slug'])->exists()) {
                    continue;
                }
                $child['parent_id'] = $parent->id;
                $child['is_active'] = true;
                AdminMenu::create($child);
                $inserted++;
            }
        }

        Toast::info(__(':count missing menu item(s) added.', ['count' => $inserted]));
    }

    public function layout(): iterable
    {
        $parentOptions = AdminMenu::roots()->active()->pluck('title', 'id')->prepend(__('-- Top Level --'), '')->all();
        $filter = (array) request('filter', []);
        $summary = $this->buildFilterSummary($filter);

        $parentFilterOptions = AdminMenu::roots()->pluck('title', 'id')->all();

        return [
            FilterPanel::make(
                fields: [
                    Input::make('filter.search')->title(__('Search'))
                        ->placeholder(__('Title / slug / route'))
                        ->value($filter['search'] ?? ''),
                    Select::make('filter.parent_id')->title(__('Parent'))
                        ->empty(__('-- Any --'), '')
                        ->options($parentFilterOptions)
                        ->value($filter['parent_id'] ?? ''),
                    Select::make('filter.is_active')->title(__('Active'))
                        ->empty(__('-- Any --'), '')
                        ->options([1 => __('Active'), 0 => __('Inactive')])
                        ->value($filter['is_active'] ?? ''),
                ],
                summary: $summary,
            ),

            Layout::table('menus', [
                TD::make('id', 'ID')->sort(),
                TD::make('title', __('Title'))
                    ->render(fn (AdminMenu $m) => ($m->parent_id ? '— ' : '') . $m->title),
                TD::make('slug', __('Slug')),
                TD::make('icon', __('Icon')),
                TD::make('route', __('Route')),
                TD::make('permission', __('Permission')),
                TD::make('sort_order', __('Sort'))->sort(),
                TD::make('is_active', __('Active'))
                    ->render(fn (AdminMenu $m) => $m->is_active
                        ? '<span class="text-success">●</span>'
                        : '<span class="text-danger">●</span>'),
                TD::make(__('Actions'))->alignRight()
                    ->render(fn (AdminMenu $m) => ModalToggle::make(__('Edit'))
                        ->icon('bs.pencil')
                        ->modal('editModal')
                        ->modalTitle(__('Edit Menu'))
                        ->method('save')
                        ->asyncParameters(['menu' => $m->id])),
            ]),

            Layout::modal('createModal', Layout::rows([
                Input::make('menu.id')->type('hidden'),
                Select::make('menu.parent_id')->title(__('Parent'))->options($parentOptions),
                Input::make('menu.title')->title(__('Title'))->required(),
                Input::make('menu.slug')->title(__('Slug'))->required(),
                Input::make('menu.icon')->title(__('Icon'))->help('e.g. bs.speedometer2'),
                Input::make('menu.route')->title(__('Route'))->help('e.g. platform.dashboard'),
                Input::make('menu.url')->title(__('URL'))->help(__('External URL, overrides route')),
                Input::make('menu.permission')->title(__('Permission'))->help('e.g. platform.orders'),
                Input::make('menu.sort_order')->title(__('Sort'))->type('number')->value(0),
                Switcher::make('menu.is_active')->title(__('Active'))->sendTrueOrFalse()->value(true),
            ]))->title(__('Create Menu'))->applyButton(__('Save')),

            Layout::modal('editModal', Layout::rows([
                Input::make('menu.id')->type('hidden'),
                Select::make('menu.parent_id')->title(__('Parent'))->options($parentOptions),
                Input::make('menu.title')->title(__('Title'))->required(),
                Input::make('menu.slug')->title(__('Slug'))->required(),
                Input::make('menu.icon')->title(__('Icon')),
                Input::make('menu.route')->title(__('Route')),
                Input::make('menu.url')->title(__('URL')),
                Input::make('menu.permission')->title(__('Permission')),
                Input::make('menu.sort_order')->title(__('Sort'))->type('number'),
                Switcher::make('menu.is_active')->title(__('Active'))->sendTrueOrFalse(),
            ]))->title(__('Edit Menu'))->applyButton(__('Save'))->async('asyncGetMenu'),
        ];
    }

    public function asyncGetMenu(AdminMenu $menu): iterable
    {
        return ['menu' => $menu];
    }

    public function save(Request $request): void
    {
        $data = $request->validate([
            'menu.parent_id' => 'nullable|integer',
            'menu.title' => 'required|string|max:100',
            'menu.slug' => 'required|string|max:50',
            'menu.icon' => 'nullable|string|max:50',
            'menu.route' => 'nullable|string|max:100',
            'menu.url' => 'nullable|string|max:255',
            'menu.permission' => 'nullable|string|max:100',
            'menu.sort_order' => 'nullable|integer',
            'menu.is_active' => 'nullable',
        ]);

        $menuData = $data['menu'];
        $menuData['is_active'] = filter_var($menuData['is_active'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $menuData['parent_id'] = $menuData['parent_id'] ?: null;

        $id = $request->input('menu.id');
        $menu = $id ? AdminMenu::findOrFail($id) : new AdminMenu();
        $menu->fill($menuData)->save();

        Toast::info(__('Menu saved.'));
    }

    public function initDefaults(): void
    {
        AdminMenu::truncate();

        foreach ($this->defaultStructure() as $item) {
            $children = $item['children'] ?? [];
            unset($item['children']);
            $item['is_active'] = true;

            $parent = AdminMenu::create($item);

            foreach ($children as $child) {
                $child['parent_id'] = $parent->id;
                $child['is_active'] = true;
                AdminMenu::create($child);
            }
        }

        Toast::info(__('Default menus initialized.'));
    }

    protected function filterRoute(): string
    {
        return 'platform.system.menus';
    }

    private function buildFilterSummary(array $f): array
    {
        $s = [];

        if (!empty($f['search'])) {
            $s[__('Search')] = $f['search'];
        }
        if (!empty($f['parent_id'])) {
            $title = AdminMenu::whereKey((int) $f['parent_id'])->value('title');
            $s[__('Parent')] = $title ?: $f['parent_id'];
        }
        if (isset($f['is_active']) && $f['is_active'] !== '') {
            $s[__('Active')] = ((int) $f['is_active']) === 1 ? __('Active') : __('Inactive');
        }

        return $s;
    }
}
