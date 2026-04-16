<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\EntityStatus;
use App\Enums\FeeType;
use App\Enums\PaymentDirection;
use App\Models\Agent;
use App\Models\Merchant;
use App\Models\MerchantPaymentType;
use App\Models\MerchantProviderPaymentType;
use App\Models\PaymentType;
use App\Models\Provider;
use App\Models\ProviderPaymentType;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        $admin = User::firstOrCreate(
            ['email' => 'admin@fluxpay.com'],
            [
                'username' => 'admin',
                'name' => 'admin',
                'password' => Hash::make('password123'),
                'permissions' => [
                    'platform.index' => true,
                    'platform.systems.roles' => true,
                    'platform.systems.users' => true,
                    'platform.systems.attachment' => true,
                    'platform.orders' => true,
                    'platform.orders.actions' => true,
                    'platform.merchants' => true,
                    'platform.agents' => true,
                    'platform.providers' => true,
                    'platform.payment-config' => true,
                    'platform.wallets' => true,
                    'platform.reports' => true,
                    'platform.banks' => true,
                    'platform.system' => true,
                ],
            ],
        );
        // Also update permissions if user already exists
        $admin->forceFill([
            'permissions' => [
                'platform.index' => true,
                'platform.systems.roles' => true,
                'platform.systems.users' => true,
                'platform.systems.attachment' => true,
                'platform.orders' => true,
                'platform.orders.actions' => true,
                'platform.merchants' => true,
                'platform.agents' => true,
                'platform.providers' => true,
                'platform.payment-config' => true,
                'platform.wallets' => true,
                'platform.reports' => true,
                'platform.banks' => true,
                'platform.system' => true,
            ],
        ])->save();

        // Assign administrator role
        $adminRole = \Orchid\Platform\Models\Role::where('slug', 'administrator')->first();
        if ($adminRole && !$admin->inRole($adminRole)) {
            $admin->addRole($adminRole);
        }
        // Level-1 agent
        $agentL1 = Agent::updateOrCreate(
            ['name' => 'Test Agent L1'],
            [
                'types' => 'merchant',
                'level' => 1,
                'status' => EntityStatus::ACTIVE->value,
                'currency' => 'PHP',
                'total_balance' => '0.00',
                'available_balance' => '0.00',
                'hold_balance' => '0.00',
            ],
        );

        // Level-2 agent under Level-1
        $agentL2 = Agent::updateOrCreate(
            ['name' => 'Test Agent L2'],
            [
                'parent_id' => $agentL1->id,
                'types' => 'merchant',
                'level' => 2,
                'status' => EntityStatus::ACTIVE->value,
                'currency' => 'PHP',
                'total_balance' => '0.00',
                'available_balance' => '0.00',
                'hold_balance' => '0.00',
            ],
        );

        // Merchant under Level-1 agent
        $merchant = Merchant::updateOrCreate(
            ['code' => 'TEST001'],
            [
                'agent_id' => $agentL1->id,
                'name' => 'Test Merchant',
                'md5key' => 'test_secret_key_123',
                'currency_code' => 'PHP',
                'status' => EntityStatus::ACTIVE->value,
                'total_balance' => '100000.00',
                'available_balance' => '100000.00',
                'hold_balance' => '0.00',
                'white_ips' => [],
            ],
        );

        // Provider
        $provider = Provider::updateOrCreate(
            ['vendor_id' => 'testpay'],
            [
                'agent_id' => $agentL1->id,
                'name' => 'Test Payment Provider',
                'provider_no' => 'TESTPAY001',
                'vendor_meta' => [],
                'currency_code' => 'PHP',
                'status' => EntityStatus::ACTIVE->value,
                'total_balance' => '0.00',
                'available_balance' => '0.00',
                'hold_balance' => '0.00',
            ],
        );

        // Get BANK_TRANSFER payment type
        $bankTransfer = PaymentType::where('payment_type_code', 'BANK_TRANSFER')->first();

        if (! $bankTransfer) {
            $bankTransfer = PaymentType::create([
                'payment_type_code' => 'BANK_TRANSFER',
                'name' => 'Bank Transfer',
                'status' => EntityStatus::ACTIVE->value,
                'sort_order' => 1,
            ]);
        }

        // ProviderPaymentType: deposit channel
        $depositChannel = ProviderPaymentType::updateOrCreate(
            [
                'provider_id' => $provider->id,
                'payment_type_id' => $bankTransfer->id,
                'type' => PaymentDirection::DEPOSIT->value,
            ],
            [
                'alias' => 'TestPay Bank Deposit',
                'status' => EntityStatus::ACTIVE->value,
                'weight' => 100,
                'single_min_amount' => '100.00',
                'single_max_amount' => '500000.00',
                'daily_amount_limit' => '10000000.00',
                'daily_count_limit' => 1000,
                'current_daily_amount' => '0.00',
                'deposit_fee_type' => FeeType::PERCENTAGE->value,
                'deposit_fee' => '1.00',
            ],
        );

        // ProviderPaymentType: withdraw channel
        $withdrawChannel = ProviderPaymentType::updateOrCreate(
            [
                'provider_id' => $provider->id,
                'payment_type_id' => $bankTransfer->id,
                'type' => PaymentDirection::WITHDRAW->value,
            ],
            [
                'alias' => 'TestPay Bank Withdraw',
                'status' => EntityStatus::ACTIVE->value,
                'weight' => 100,
                'single_min_amount' => '100.00',
                'single_max_amount' => '500000.00',
                'daily_amount_limit' => '10000000.00',
                'daily_count_limit' => 1000,
                'current_daily_amount' => '0.00',
                'withdraw_fee_type' => FeeType::PERCENTAGE->value,
                'withdraw_fee' => '1.00',
            ],
        );

        // MerchantPaymentType
        $mpt = MerchantPaymentType::updateOrCreate(
            [
                'merchant_id' => $merchant->id,
                'payment_type_id' => $bankTransfer->id,
            ],
            [
                'status' => EntityStatus::ACTIVE->value,
                'single_min_amount' => '100.00',
                'single_max_amount' => '500000.00',
                'deposit_fee_type' => FeeType::PERCENTAGE->value,
                'deposit_fee' => '2.00',
                'deposit_agents_fee' => [
                    $agentL1->id => '0.5',
                    $agentL2->id => '0.3',
                ],
                'withdraw_fee_type' => FeeType::PERCENTAGE->value,
                'withdraw_fee' => '2.00',
                'withdraw_agents_fee' => [
                    $agentL1->id => '0.5',
                    $agentL2->id => '0.3',
                ],
            ],
        );

        // MerchantProviderPaymentType: link merchant to deposit channel
        MerchantProviderPaymentType::updateOrCreate(
            [
                'merchant_id' => $merchant->id,
                'provider_payment_type_id' => $depositChannel->id,
            ],
            [
                'status' => EntityStatus::ACTIVE->value,
            ],
        );

        // MerchantProviderPaymentType: link merchant to withdraw channel
        MerchantProviderPaymentType::updateOrCreate(
            [
                'merchant_id' => $merchant->id,
                'provider_payment_type_id' => $withdrawChannel->id,
            ],
            [
                'status' => EntityStatus::ACTIVE->value,
            ],
        );
    }
}
