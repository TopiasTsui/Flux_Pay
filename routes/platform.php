<?php

declare(strict_types=1);

use App\Orchid\Screens\Agent\AgentEditScreen;
use App\Orchid\Screens\Agent\AgentListScreen;
use App\Orchid\Screens\Bank\BankListScreen;
use App\Orchid\Screens\Bank\ProviderBankCodeScreen;
use App\Orchid\Screens\Dashboard\AdminDashboardScreen;
use App\Orchid\Screens\Merchant\MerchantEditScreen;
use App\Orchid\Screens\Merchant\MerchantListScreen;
use App\Orchid\Screens\Order\DepositOrderDetailScreen;
use App\Orchid\Screens\Order\DepositOrderListScreen;
use App\Orchid\Screens\Order\WithdrawOrderDetailScreen;
use App\Orchid\Screens\Order\WithdrawOrderListScreen;
use App\Orchid\Screens\PaymentConfig\MerchantPaymentTypeEditScreen;
use App\Orchid\Screens\PaymentConfig\MerchantPaymentTypeListScreen;
use App\Orchid\Screens\PaymentConfig\PaymentTypeListScreen;
use App\Orchid\Screens\PaymentConfig\ProviderPaymentTypeEditScreen;
use App\Orchid\Screens\PaymentConfig\ProviderPaymentTypeListScreen;
use App\Orchid\Screens\Provider\ProviderEditScreen;
use App\Orchid\Screens\Provider\ProviderListScreen;
use App\Orchid\Screens\Report\DailyRevenueStatScreen;
use App\Orchid\Screens\Report\DailyTransactionStatScreen;
use App\Orchid\Screens\Role\RoleEditScreen;
use App\Orchid\Screens\Role\RoleListScreen;
use App\Orchid\Screens\System\BlacklistScreen;
use App\Orchid\Screens\System\LocaleListScreen;
use App\Orchid\Screens\System\MenuManagementScreen;
use App\Orchid\Screens\System\SystemConfigScreen;
use App\Orchid\Screens\System\TranslationListScreen;
use App\Http\Controllers\Platform\TwoFactorController;
use App\Orchid\Screens\User\UserEditScreen;
use App\Orchid\Screens\User\UserListScreen;
use App\Orchid\Screens\User\UserProfileScreen;
use App\Orchid\Screens\Wallet\AgentWalletListScreen;
use App\Orchid\Screens\Wallet\MerchantWalletListScreen;
use App\Orchid\Screens\Wallet\ProviderWalletListScreen;
use Illuminate\Support\Facades\Route;
use Tabuna\Breadcrumbs\Trail;

/*
|--------------------------------------------------------------------------
| Dashboard Routes
|--------------------------------------------------------------------------
*/

// Two-factor challenge — runs after Orchid login, before reaching the dashboard.
Route::get('/2fa/challenge', [TwoFactorController::class, 'challenge'])->name('platform.2fa.challenge');
Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])->name('platform.2fa.verify');

// Dashboard
Route::screen('/main', AdminDashboardScreen::class)
    ->name('platform.main');

Route::screen('dashboard', AdminDashboardScreen::class)
    ->name('platform.dashboard');

// Profile
Route::screen('profile', UserProfileScreen::class)
    ->name('platform.profile')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Profile'), route('platform.profile')));

// ── Agents ──────────────────────────────────────────────────────────────
Route::screen('agents', AgentListScreen::class)
    ->name('platform.agents')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Agents', route('platform.agents')));

Route::screen('agents/create', AgentEditScreen::class)
    ->name('platform.agents.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.agents')
        ->push('Create'));

Route::screen('agents/{agent}/edit', AgentEditScreen::class)
    ->name('platform.agents.edit')
    ->breadcrumbs(fn (Trail $trail, $agent) => $trail
        ->parent('platform.agents')
        ->push('Edit'));

// ── Merchants ───────────────────────────────────────────────────────────
Route::screen('merchants', MerchantListScreen::class)
    ->name('platform.merchants')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Merchants', route('platform.merchants')));

Route::screen('merchants/create', MerchantEditScreen::class)
    ->name('platform.merchants.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.merchants')
        ->push('Create'));

Route::screen('merchants/{merchant}/edit', MerchantEditScreen::class)
    ->name('platform.merchants.edit')
    ->breadcrumbs(fn (Trail $trail, $merchant) => $trail
        ->parent('platform.merchants')
        ->push('Edit'));

// ── Providers ───────────────────────────────────────────────────────────
Route::screen('providers', ProviderListScreen::class)
    ->name('platform.providers')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Providers', route('platform.providers')));

Route::screen('providers/create', ProviderEditScreen::class)
    ->name('platform.providers.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.providers')
        ->push('Create'));

Route::screen('providers/{provider}/edit', ProviderEditScreen::class)
    ->name('platform.providers.edit')
    ->breadcrumbs(fn (Trail $trail, $provider) => $trail
        ->parent('platform.providers')
        ->push('Edit'));

// ── Payment Types ───────────────────────────────────────────────────────
Route::screen('payment-types', PaymentTypeListScreen::class)
    ->name('platform.payment-types')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Payment Types', route('platform.payment-types')));

// ── Provider Payment Types (Channels) ───────────────────────────────────
Route::screen('provider-payment-types', ProviderPaymentTypeListScreen::class)
    ->name('platform.provider-payment-types')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Provider Channels', route('platform.provider-payment-types')));

Route::screen('provider-payment-types/create', ProviderPaymentTypeEditScreen::class)
    ->name('platform.provider-payment-types.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.provider-payment-types')
        ->push('Create'));

Route::screen('provider-payment-types/{channel}/edit', ProviderPaymentTypeEditScreen::class)
    ->name('platform.provider-payment-types.edit')
    ->breadcrumbs(fn (Trail $trail, $channel) => $trail
        ->parent('platform.provider-payment-types')
        ->push('Edit'));

// ── Merchant Payment Types ──────────────────────────────────────────────
Route::screen('merchant-payment-types', MerchantPaymentTypeListScreen::class)
    ->name('platform.merchant-payment-types')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Merchant Fee Config', route('platform.merchant-payment-types')));

Route::screen('merchant-payment-types/create', MerchantPaymentTypeEditScreen::class)
    ->name('platform.merchant-payment-types.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.merchant-payment-types')
        ->push('Create'));

Route::screen('merchant-payment-types/{config}/edit', MerchantPaymentTypeEditScreen::class)
    ->name('platform.merchant-payment-types.edit')
    ->breadcrumbs(fn (Trail $trail, $config) => $trail
        ->parent('platform.merchant-payment-types')
        ->push('Edit'));

// ── Deposit Orders ──────────────────────────────────────────────────────
Route::screen('deposit-orders', DepositOrderListScreen::class)
    ->name('platform.deposit-orders')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Deposit Orders', route('platform.deposit-orders')));

Route::screen('deposit-orders/{order}', DepositOrderDetailScreen::class)
    ->name('platform.deposit-orders.detail')
    ->breadcrumbs(fn (Trail $trail, $order) => $trail
        ->parent('platform.deposit-orders')
        ->push('Detail'));

// ── Withdraw Orders ─────────────────────────────────────────────────────
Route::screen('withdraw-orders', WithdrawOrderListScreen::class)
    ->name('platform.withdraw-orders')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Withdraw Orders', route('platform.withdraw-orders')));

Route::screen('withdraw-orders/{order}', WithdrawOrderDetailScreen::class)
    ->name('platform.withdraw-orders.detail')
    ->breadcrumbs(fn (Trail $trail, $order) => $trail
        ->parent('platform.withdraw-orders')
        ->push('Detail'));

// ── Wallets ─────────────────────────────────────────────────────────────
Route::screen('wallets/merchant', MerchantWalletListScreen::class)
    ->name('platform.wallets.merchant')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Merchant Wallet', route('platform.wallets.merchant')));

Route::screen('wallets/agent', AgentWalletListScreen::class)
    ->name('platform.wallets.agent')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Agent Wallet', route('platform.wallets.agent')));

Route::screen('wallets/provider', ProviderWalletListScreen::class)
    ->name('platform.wallets.provider')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Provider Wallet', route('platform.wallets.provider')));

// ── Reports ─────────────────────────────────────────────────────────────
Route::screen('reports/transactions', DailyTransactionStatScreen::class)
    ->name('platform.reports.transactions')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Transaction Stats', route('platform.reports.transactions')));

Route::screen('reports/revenue', DailyRevenueStatScreen::class)
    ->name('platform.reports.revenue')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Revenue Stats', route('platform.reports.revenue')));

// ── Banks ───────────────────────────────────────────────────────────────
Route::screen('banks', BankListScreen::class)
    ->name('platform.banks')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Banks', route('platform.banks')));

Route::screen('provider-bank-codes', ProviderBankCodeScreen::class)
    ->name('platform.provider-bank-codes')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Provider Bank Codes', route('platform.provider-bank-codes')));

// ── System ──────────────────────────────────────────────────────────────
Route::screen('system/configs', SystemConfigScreen::class)
    ->name('platform.system.configs')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('System Config', route('platform.system.configs')));

Route::screen('system/blacklist', BlacklistScreen::class)
    ->name('platform.system.blacklist')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push('Blacklist', route('platform.system.blacklist')));

Route::screen('system/menus', MenuManagementScreen::class)
    ->name('platform.system.menus')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Menu Management'), route('platform.system.menus')));

Route::screen('system/i18n/locales', LocaleListScreen::class)
    ->name('platform.system.i18n.locales')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Locales'), route('platform.system.i18n.locales')));

Route::screen('system/i18n/translations', TranslationListScreen::class)
    ->name('platform.system.i18n.translations')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Translations'), route('platform.system.i18n.translations')));

// ── Access Controls ─────────────────────────────────────────────────────
Route::screen('users/{user}/edit', UserEditScreen::class)
    ->name('platform.systems.users.edit')
    ->breadcrumbs(fn (Trail $trail, $user) => $trail
        ->parent('platform.systems.users')
        ->push($user->name, route('platform.systems.users.edit', $user)));

Route::screen('users/create', UserEditScreen::class)
    ->name('platform.systems.users.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.users')
        ->push(__('Create'), route('platform.systems.users.create')));

Route::screen('users', UserListScreen::class)
    ->name('platform.systems.users')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Users'), route('platform.systems.users')));

Route::screen('roles/{role}/edit', RoleEditScreen::class)
    ->name('platform.systems.roles.edit')
    ->breadcrumbs(fn (Trail $trail, $role) => $trail
        ->parent('platform.systems.roles')
        ->push($role->name, route('platform.systems.roles.edit', $role)));

Route::screen('roles/create', RoleEditScreen::class)
    ->name('platform.systems.roles.create')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.systems.roles')
        ->push(__('Create'), route('platform.systems.roles.create')));

Route::screen('roles', RoleListScreen::class)
    ->name('platform.systems.roles')
    ->breadcrumbs(fn (Trail $trail) => $trail
        ->parent('platform.index')
        ->push(__('Roles'), route('platform.systems.roles')));
