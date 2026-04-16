<?php

declare(strict_types=1);

namespace App\Orchid;

use App\Models\AdminMenu;
use Orchid\Platform\Dashboard;
use Orchid\Platform\ItemPermission;
use Orchid\Platform\OrchidServiceProvider;
use Orchid\Screen\Actions\Menu;

class PlatformProvider extends OrchidServiceProvider
{
    public function boot(Dashboard $dashboard): void
    {
        parent::boot($dashboard);
    }

    public function menu(): array
    {
        try {
            $dbMenus = AdminMenu::active()->roots()->orderBy('sort_order')->with('children')->get();
            if ($dbMenus->isNotEmpty()) {
                return $this->buildMenuFromDb($dbMenus);
            }
        } catch (\Throwable $e) {
            // DB not ready yet (migration not run), fall through to defaults
        }

        return $this->defaultMenu();
    }

    private function buildMenuFromDb($roots): array
    {
        $menus = [];

        foreach ($roots as $root) {
            $activeChildren = $root->children->where('is_active', true)->sortBy('sort_order');

            if ($activeChildren->isEmpty()) {
                // Leaf item
                $item = Menu::make(__($root->title))
                    ->icon($root->icon ?? 'bs.circle')
                    ->slug($root->slug);

                if ($root->route) {
                    $item->route($root->route);
                } elseif ($root->url) {
                    $item->url($root->url);
                }

                if ($root->permission) {
                    $item->permission($root->permission);
                }

                $menus[] = $item;
            } else {
                // Parent with children
                $childItems = [];
                foreach ($activeChildren as $child) {
                    $childItem = Menu::make(__($child->title))
                        ->icon($child->icon ?? 'bs.circle');

                    if ($child->route) {
                        $childItem->route($child->route);
                    } elseif ($child->url) {
                        $childItem->url($child->url);
                    }

                    if ($child->permission) {
                        $childItem->permission($child->permission);
                    }

                    $childItems[] = $childItem;
                }

                $parent = Menu::make(__($root->title))
                    ->icon($root->icon ?? 'bs.circle')
                    ->slug($root->slug)
                    ->list($childItems);

                if ($root->permission) {
                    $parent->permission($root->permission);
                }

                $menus[] = $parent;
            }
        }

        return $menus;
    }

    private function defaultMenu(): array
    {
        return [
            Menu::make(__('Dashboard'))
                ->icon('bs.speedometer2')
                ->route('platform.dashboard'),

            Menu::make(__('Payment Management'))
                ->icon('bs.credit-card-2-front')
                ->slug('payment-management')
                ->permission('platform.orders')
                ->list([
                    Menu::make(__('Deposit Orders'))->icon('bs.arrow-down-circle')->route('platform.deposit-orders'),
                    Menu::make(__('Withdraw Orders'))->icon('bs.arrow-up-circle')->route('platform.withdraw-orders'),
                ]),

            Menu::make(__('Entity Management'))
                ->icon('bs.building')
                ->slug('entity-management')
                ->list([
                    Menu::make(__('Merchants'))->icon('bs.shop')->route('platform.merchants')->permission('platform.merchants'),
                    Menu::make(__('Agents'))->icon('bs.people')->route('platform.agents')->permission('platform.agents'),
                    Menu::make(__('Providers'))->icon('bs.plug')->route('platform.providers')->permission('platform.providers'),
                ]),

            Menu::make(__('Payment Config'))
                ->icon('bs.sliders')
                ->slug('payment-config')
                ->permission('platform.payment-config')
                ->list([
                    Menu::make(__('Payment Types'))->icon('bs.credit-card')->route('platform.payment-types'),
                    Menu::make(__('Provider Channels'))->icon('bs.diagram-3')->route('platform.provider-payment-types'),
                    Menu::make(__('Merchant Fee Config'))->icon('bs.calculator')->route('platform.merchant-payment-types'),
                ]),

            Menu::make(__('Finance'))
                ->icon('bs.wallet2')
                ->slug('finance')
                ->permission('platform.wallets')
                ->list([
                    Menu::make(__('Merchant Wallet'))->icon('bs.wallet2')->route('platform.wallets.merchant'),
                    Menu::make(__('Agent Wallet'))->icon('bs.wallet')->route('platform.wallets.agent'),
                    Menu::make(__('Provider Wallet'))->icon('bs.wallet-fill')->route('platform.wallets.provider'),
                ]),

            Menu::make(__('Reports'))
                ->icon('bs.graph-up')
                ->slug('reports')
                ->permission('platform.reports')
                ->list([
                    Menu::make(__('Transaction Stats'))->icon('bs.graph-up')->route('platform.reports.transactions'),
                    Menu::make(__('Revenue Stats'))->icon('bs.currency-dollar')->route('platform.reports.revenue'),
                ]),

            Menu::make(__('Banks'))
                ->icon('bs.bank')
                ->slug('banks')
                ->permission('platform.banks')
                ->list([
                    Menu::make(__('Bank List'))->icon('bs.bank')->route('platform.banks'),
                    Menu::make(__('Provider Bank Codes'))->icon('bs.link-45deg')->route('platform.provider-bank-codes'),
                ]),

            Menu::make(__('System'))
                ->icon('bs.gear')
                ->slug('system')
                ->permission('platform.system')
                ->list([
                    Menu::make(__('System Config'))->icon('bs.gear')->route('platform.system.configs'),
                    Menu::make(__('Blacklist'))->icon('bs.shield-x')->route('platform.system.blacklist'),
                    Menu::make(__('Menu Management'))->icon('bs.list')->route('platform.system.menus'),
                ]),

            Menu::make(__('Access Controls'))
                ->icon('bs.lock')
                ->slug('access-controls')
                ->list([
                    Menu::make(__('Users'))->icon('bs.people')->route('platform.systems.users')->permission('platform.systems.users'),
                    Menu::make(__('Roles'))->icon('bs.shield')->route('platform.systems.roles')->permission('platform.systems.roles'),
                ]),
        ];
    }

    public function permissions(): array
    {
        return [
            ItemPermission::group(__('Payment'))
                ->addPermission('platform.orders', __('Orders'))
                ->addPermission('platform.orders.actions', __('Order Actions (Query/Callback)')),

            ItemPermission::group(__('Entities'))
                ->addPermission('platform.merchants', __('Merchants'))
                ->addPermission('platform.agents', __('Agents'))
                ->addPermission('platform.providers', __('Providers')),

            ItemPermission::group(__('Payment Config'))
                ->addPermission('platform.payment-config', __('Payment Configuration')),

            ItemPermission::group(__('Finance'))
                ->addPermission('platform.wallets', __('Wallet Records')),

            ItemPermission::group(__('Reports'))
                ->addPermission('platform.reports', __('Reports')),

            ItemPermission::group(__('Banks'))
                ->addPermission('platform.banks', __('Bank Management')),

            ItemPermission::group(__('System'))
                ->addPermission('platform.system', __('System Configuration'))
                ->addPermission('platform.systems.roles', __('Roles'))
                ->addPermission('platform.systems.users', __('Users')),
        ];
    }
}
