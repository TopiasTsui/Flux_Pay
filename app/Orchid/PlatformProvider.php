<?php

declare(strict_types=1);

namespace App\Orchid;

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
        return [
            Menu::make(__('Dashboard'))
                ->icon('bs.speedometer2')
                ->route('platform.dashboard'),

            Menu::make(__('Payment Management'))
                ->icon('bs.credit-card-2-front')
                ->permission('platform.orders')
                ->list([
                    Menu::make(__('Deposit Orders'))
                        ->icon('bs.arrow-down-circle')
                        ->route('platform.deposit-orders'),

                    Menu::make(__('Withdraw Orders'))
                        ->icon('bs.arrow-up-circle')
                        ->route('platform.withdraw-orders'),
                ]),

            Menu::make(__('Entity Management'))
                ->icon('bs.building')
                ->list([
                    Menu::make(__('Merchants'))
                        ->icon('bs.shop')
                        ->route('platform.merchants')
                        ->permission('platform.merchants'),

                    Menu::make(__('Agents'))
                        ->icon('bs.people')
                        ->route('platform.agents')
                        ->permission('platform.agents'),

                    Menu::make(__('Providers'))
                        ->icon('bs.plug')
                        ->route('platform.providers')
                        ->permission('platform.providers'),
                ]),

            Menu::make(__('Payment Config'))
                ->icon('bs.sliders')
                ->permission('platform.payment-config')
                ->list([
                    Menu::make(__('Payment Types'))
                        ->icon('bs.credit-card')
                        ->route('platform.payment-types'),

                    Menu::make(__('Provider Channels'))
                        ->icon('bs.diagram-3')
                        ->route('platform.provider-payment-types'),

                    Menu::make(__('Merchant Fee Config'))
                        ->icon('bs.calculator')
                        ->route('platform.merchant-payment-types'),
                ]),

            Menu::make(__('Finance'))
                ->icon('bs.wallet2')
                ->permission('platform.wallets')
                ->list([
                    Menu::make(__('Merchant Wallet'))
                        ->icon('bs.wallet2')
                        ->route('platform.wallets.merchant'),

                    Menu::make(__('Agent Wallet'))
                        ->icon('bs.wallet')
                        ->route('platform.wallets.agent'),

                    Menu::make(__('Provider Wallet'))
                        ->icon('bs.wallet-fill')
                        ->route('platform.wallets.provider'),
                ]),

            Menu::make(__('Reports'))
                ->icon('bs.graph-up')
                ->permission('platform.reports')
                ->list([
                    Menu::make(__('Transaction Stats'))
                        ->icon('bs.graph-up')
                        ->route('platform.reports.transactions'),

                    Menu::make(__('Revenue Stats'))
                        ->icon('bs.currency-dollar')
                        ->route('platform.reports.revenue'),
                ]),

            Menu::make(__('Banks'))
                ->icon('bs.bank')
                ->permission('platform.banks')
                ->list([
                    Menu::make(__('Bank List'))
                        ->icon('bs.bank')
                        ->route('platform.banks'),

                    Menu::make(__('Provider Bank Codes'))
                        ->icon('bs.link-45deg')
                        ->route('platform.provider-bank-codes'),
                ]),

            Menu::make(__('System'))
                ->icon('bs.gear')
                ->permission('platform.system')
                ->list([
                    Menu::make(__('System Config'))
                        ->icon('bs.gear')
                        ->route('platform.system.configs'),

                    Menu::make(__('Blacklist'))
                        ->icon('bs.shield-x')
                        ->route('platform.system.blacklist'),

                    Menu::make(__('Proxies'))
                        ->icon('bs.globe')
                        ->route('platform.system.proxies'),
                ]),

            Menu::make(__('Access Controls'))
                ->icon('bs.lock')
                ->list([
                    Menu::make(__('Users'))
                        ->icon('bs.people')
                        ->route('platform.systems.users')
                        ->permission('platform.systems.users'),

                    Menu::make(__('Roles'))
                        ->icon('bs.shield')
                        ->route('platform.systems.roles')
                        ->permission('platform.systems.roles'),
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
