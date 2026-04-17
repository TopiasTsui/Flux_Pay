<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\BankSeeder;
use Database\Seeders\LocaleSeeder;
use Database\Seeders\PaymentTypeSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\TestDataSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Exercise each list screen with sample filter query params.
 *
 * Each row in the data provider yields:
 *   [route path, query string]
 *
 * If any filter value crashes the screen (bad cast, null dereference, missing
 * enum branch, etc.), the smoke test catches it. Empty strings are also included
 * because users frequently submit the form with some fields blank.
 */
class FilterSmokeTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->seed(PaymentTypeSeeder::class);
        $this->seed(BankSeeder::class);
        $this->seed(LocaleSeeder::class);
        $this->seed(TestDataSeeder::class);

        $this->admin = User::where('email', 'admin@fluxpay.com')->first();
    }

    #[Test]
    #[DataProvider('filteredRoutes')]
    public function list_screens_render_with_filters(string $path, array $filter): void
    {
        $response = $this->actingAs($this->admin)->get($path . '?' . http_build_query(['filter' => $filter]));

        $response->assertStatus(200);
    }

    public static function filteredRoutes(): array
    {
        return [
            'deposit-orders + status' => ['/admin/deposit-orders', ['status' => 4]],
            'deposit-orders + search' => ['/admin/deposit-orders', ['merchant_code' => 'TEST']],
            'deposit-orders + date'   => ['/admin/deposit-orders', ['date' => ['start' => '2026-01-01', 'end' => '2026-12-31']]],
            'deposit-orders + empty'  => ['/admin/deposit-orders', ['status' => '']],

            'withdraw-orders + status' => ['/admin/withdraw-orders', ['status' => 3]],
            'withdraw-orders + bank'   => ['/admin/withdraw-orders', ['bank_account_no' => '123']],

            'merchant-wallet + date'    => ['/admin/wallets/merchant', ['date' => ['start' => '2026-01-01']]],
            'merchant-wallet + type'    => ['/admin/wallets/merchant', ['type_code' => 'deposit_income']],
            'agent-wallet + merchant'   => ['/admin/wallets/agent', ['agent_id' => '1']],
            'provider-wallet'           => ['/admin/wallets/provider', ['sn' => 'X']],

            'merchants + code'    => ['/admin/merchants', ['code' => 'TEST']],
            'merchants + status'  => ['/admin/merchants', ['status' => 1]],
            'agents + type'       => ['/admin/agents', ['types' => 'merchant']],
            'agents + level'      => ['/admin/agents', ['level' => 1]],
            'providers + vendor'  => ['/admin/providers', ['vendor_id' => 'testpay']],

            'payment-types + code'          => ['/admin/payment-types', ['payment_type_code' => 'BANK']],
            'provider-payment-types + type' => ['/admin/provider-payment-types', ['type' => 'deposit']],
            'merchant-payment-types'        => ['/admin/merchant-payment-types', ['merchant_id' => '1']],

            'reports-transactions' => ['/admin/reports/transactions', ['date' => ['start' => '2026-04-01', 'end' => '2026-04-30']]],
            'reports-revenue'      => ['/admin/reports/revenue', ['date' => ['start' => '2026-04-01']]],

            'banks'                 => ['/admin/banks', ['name' => 'Bank']],
            'provider-bank-codes'   => ['/admin/provider-bank-codes', ['bank_code' => 'X']],
            'system-configs + group' => ['/admin/system/configs', ['group' => 'general']],
            'system-blacklist'       => ['/admin/system/blacklist', ['type' => 'ip']],
            'system-menus'           => ['/admin/system/menus', ['search' => 'dashboard']],
            'i18n-locales'           => ['/admin/system/i18n/locales', ['is_active' => 1]],
            'i18n-translations'      => ['/admin/system/i18n/translations', ['locale' => 'zh-CN', 'search' => 'Dashboard']],

            'users + search' => ['/admin/users', ['search' => 'admin']],
            'users + role'   => ['/admin/users', ['role' => 'administrator']],
            'roles + search' => ['/admin/roles', ['search' => 'admin']],
        ];
    }

    #[Test]
    #[DataProvider('filteredRoutes')]
    public function clear_filter_redirects_back(string $path, array $filter): void
    {
        // POST to clearFilter method. Orchid routes screen methods via the
        // route's /{method?} optional path segment.
        $response = $this->actingAs($this->admin)
            ->post($path . '/clearFilter', ['_token' => csrf_token()]);

        $response->assertStatus(302);
    }
}
