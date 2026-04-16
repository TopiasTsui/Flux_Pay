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
                'name' => 'admin',
                'password' => Hash::make('password123'),
            ],
        );
        // Assign all permissions via Orchid
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
                'status' => EntityStatus::ACTIVE,
                'currency' => 'PHP',
                'total_balance' => '0.000000',
                'available_balance' => '0.000000',
                'hold_balance' => '0.000000',
            ],
        );

        // Level-2 agent under Level-1
        $agentL2 = Agent::updateOrCreate(
            ['name' => 'Test Agent L2'],
            [
                'parent_id' => $agentL1->id,
                'types' => 'merchant',
                'level' => 2,
                'status' => EntityStatus::ACTIVE,
                'currency' => 'PHP',
                'total_balance' => '0.000000',
                'available_balance' => '0.000000',
                'hold_balance' => '0.000000',
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
                'status' => EntityStatus::ACTIVE,
                'total_balance' => '100000.000000',
                'available_balance' => '100000.000000',
                'hold_balance' => '0.000000',
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
                'status' => EntityStatus::ACTIVE,
                'total_balance' => '0.000000',
                'available_balance' => '0.000000',
                'hold_balance' => '0.000000',
            ],
        );

        // Get BANK_TRANSFER payment type
        $bankTransfer = PaymentType::where('payment_type_code', 'BANK_TRANSFER')->first();

        if (! $bankTransfer) {
            $bankTransfer = PaymentType::create([
                'payment_type_code' => 'BANK_TRANSFER',
                'name' => 'Bank Transfer',
                'status' => EntityStatus::ACTIVE,
                'sort_order' => 1,
            ]);
        }

        // ProviderPaymentType: deposit channel
        $depositChannel = ProviderPaymentType::updateOrCreate(
            [
                'provider_id' => $provider->id,
                'payment_type_id' => $bankTransfer->id,
                'type' => PaymentDirection::DEPOSIT,
            ],
            [
                'alias' => 'TestPay Bank Deposit',
                'status' => EntityStatus::ACTIVE,
                'weight' => 100,
                'single_min_amount' => '100.000000',
                'single_max_amount' => '500000.000000',
                'daily_amount_limit' => '10000000.000000',
                'daily_count_limit' => 1000,
                'current_daily_amount' => '0.000000',
                'deposit_fee_type' => FeeType::PERCENTAGE,
                'deposit_fee' => '1.000000',
            ],
        );

        // ProviderPaymentType: withdraw channel
        $withdrawChannel = ProviderPaymentType::updateOrCreate(
            [
                'provider_id' => $provider->id,
                'payment_type_id' => $bankTransfer->id,
                'type' => PaymentDirection::WITHDRAW,
            ],
            [
                'alias' => 'TestPay Bank Withdraw',
                'status' => EntityStatus::ACTIVE,
                'weight' => 100,
                'single_min_amount' => '100.000000',
                'single_max_amount' => '500000.000000',
                'daily_amount_limit' => '10000000.000000',
                'daily_count_limit' => 1000,
                'current_daily_amount' => '0.000000',
                'withdraw_fee_type' => FeeType::PERCENTAGE,
                'withdraw_fee' => '1.000000',
            ],
        );

        // MerchantPaymentType
        $mpt = MerchantPaymentType::updateOrCreate(
            [
                'merchant_id' => $merchant->id,
                'payment_type_id' => $bankTransfer->id,
            ],
            [
                'status' => EntityStatus::ACTIVE,
                'single_min_amount' => '100.000000',
                'single_max_amount' => '500000.000000',
                'deposit_fee_type' => FeeType::PERCENTAGE,
                'deposit_fee' => '2.000000',
                'deposit_agents_fee' => [
                    $agentL1->id => '0.5',
                    $agentL2->id => '0.3',
                ],
                'withdraw_fee_type' => FeeType::PERCENTAGE,
                'withdraw_fee' => '2.000000',
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
                'status' => EntityStatus::ACTIVE,
            ],
        );

        // MerchantProviderPaymentType: link merchant to withdraw channel
        MerchantProviderPaymentType::updateOrCreate(
            [
                'merchant_id' => $merchant->id,
                'provider_payment_type_id' => $withdrawChannel->id,
            ],
            [
                'status' => EntityStatus::ACTIVE,
            ],
        );
    }
}
