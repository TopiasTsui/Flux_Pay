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
            // Dashboard
            Menu::make('Dashboard')
                ->icon('bs.speedometer2')
                ->route('platform.dashboard')
                ->title('Navigation'),

            // Payment Management
            Menu::make('Deposit Orders')
                ->icon('bs.arrow-down-circle')
                ->route('platform.deposit-orders')
                ->permission('platform.orders')
                ->title('Payment Management'),

            Menu::make('Withdraw Orders')
                ->icon('bs.arrow-up-circle')
                ->route('platform.withdraw-orders')
                ->permission('platform.orders'),

            // Entity Management
            Menu::make('Merchants')
                ->icon('bs.shop')
                ->route('platform.merchants')
                ->permission('platform.merchants')
                ->title('Entity Management'),

            Menu::make('Agents')
                ->icon('bs.people')
                ->route('platform.agents')
                ->permission('platform.agents'),

            Menu::make('Providers')
                ->icon('bs.plug')
                ->route('platform.providers')
                ->permission('platform.providers'),

            // Payment Config
            Menu::make('Payment Types')
                ->icon('bs.credit-card')
                ->route('platform.payment-types')
                ->permission('platform.payment-config')
                ->title('Payment Config'),

            Menu::make('Provider Channels')
                ->icon('bs.diagram-3')
                ->route('platform.provider-payment-types')
                ->permission('platform.payment-config'),

            Menu::make('Merchant Fee Config')
                ->icon('bs.calculator')
                ->route('platform.merchant-payment-types')
                ->permission('platform.payment-config'),

            // Finance
            Menu::make('Merchant Wallet')
                ->icon('bs.wallet2')
                ->route('platform.wallets.merchant')
                ->permission('platform.wallets')
                ->title('Finance'),

            Menu::make('Agent Wallet')
                ->icon('bs.wallet')
                ->route('platform.wallets.agent')
                ->permission('platform.wallets'),

            Menu::make('Provider Wallet')
                ->icon('bs.wallet-fill')
                ->route('platform.wallets.provider')
                ->permission('platform.wallets'),

            // Reports
            Menu::make('Transaction Stats')
                ->icon('bs.graph-up')
                ->route('platform.reports.transactions')
                ->permission('platform.reports')
                ->title('Reports'),

            Menu::make('Revenue Stats')
                ->icon('bs.currency-dollar')
                ->route('platform.reports.revenue')
                ->permission('platform.reports'),

            // Banks
            Menu::make('Banks')
                ->icon('bs.bank')
                ->route('platform.banks')
                ->permission('platform.banks')
                ->title('Banks'),

            Menu::make('Provider Bank Codes')
                ->icon('bs.link-45deg')
                ->route('platform.provider-bank-codes')
                ->permission('platform.banks'),

            // System
            Menu::make('System Config')
                ->icon('bs.gear')
                ->route('platform.system.configs')
                ->permission('platform.system')
                ->title('System'),

            Menu::make('Blacklist')
                ->icon('bs.shield-x')
                ->route('platform.system.blacklist')
                ->permission('platform.system'),

            Menu::make('Proxies')
                ->icon('bs.globe')
                ->route('platform.system.proxies')
                ->permission('platform.system')
                ->divider(),

            // Access Controls
            Menu::make(__('Users'))
                ->icon('bs.people')
                ->route('platform.systems.users')
                ->permission('platform.systems.users')
                ->title(__('Access Controls')),

            Menu::make(__('Roles'))
                ->icon('bs.shield')
                ->route('platform.systems.roles')
                ->permission('platform.systems.roles'),
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
