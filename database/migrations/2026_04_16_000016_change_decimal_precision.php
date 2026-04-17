<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SQLite has no strict decimal precision and doesn't support MODIFY — skip there.
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // agents
        DB::statement("ALTER TABLE agents MODIFY total_balance DECIMAL(20,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE agents MODIFY available_balance DECIMAL(20,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE agents MODIFY hold_balance DECIMAL(20,2) NOT NULL DEFAULT 0");

        // merchants
        DB::statement("ALTER TABLE merchants MODIFY total_balance DECIMAL(20,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE merchants MODIFY available_balance DECIMAL(20,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE merchants MODIFY hold_balance DECIMAL(20,2) NOT NULL DEFAULT 0");

        // providers
        DB::statement("ALTER TABLE providers MODIFY total_balance DECIMAL(20,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE providers MODIFY available_balance DECIMAL(20,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE providers MODIFY hold_balance DECIMAL(20,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE providers MODIFY api_available_balance DECIMAL(20,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE providers MODIFY api_hold_balance DECIMAL(20,2) NOT NULL DEFAULT 0");

        // deposit_orders
        DB::statement("ALTER TABLE deposit_orders MODIFY order_amount DECIMAL(20,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE deposit_orders MODIFY actual_amount DECIMAL(20,2) NULL");
        DB::statement("ALTER TABLE deposit_orders MODIFY merchant_balance_change DECIMAL(20,2) NULL");
        DB::statement("ALTER TABLE deposit_orders MODIFY merchant_fee DECIMAL(20,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE deposit_orders MODIFY provider_fee DECIMAL(20,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE deposit_orders MODIFY agent_fee DECIMAL(20,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE deposit_orders MODIFY provider_agent_fee DECIMAL(20,2) NOT NULL DEFAULT 0");

        // withdraw_orders
        DB::statement("ALTER TABLE withdraw_orders MODIFY order_amount DECIMAL(20,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE withdraw_orders MODIFY actual_amount DECIMAL(20,2) NULL");
        DB::statement("ALTER TABLE withdraw_orders MODIFY merchant_fee DECIMAL(20,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE withdraw_orders MODIFY provider_fee DECIMAL(20,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE withdraw_orders MODIFY agent_fee DECIMAL(20,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE withdraw_orders MODIFY provider_agent_fee DECIMAL(20,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE withdraw_orders MODIFY total_debit DECIMAL(20,2) NOT NULL DEFAULT 0");

        // merchant_wallet_records
        DB::statement("ALTER TABLE merchant_wallet_records MODIFY amount DECIMAL(20,2) NOT NULL");
        DB::statement("ALTER TABLE merchant_wallet_records MODIFY pre_total_balance DECIMAL(20,2) NOT NULL");
        DB::statement("ALTER TABLE merchant_wallet_records MODIFY pre_available_balance DECIMAL(20,2) NOT NULL");
        DB::statement("ALTER TABLE merchant_wallet_records MODIFY pre_hold_balance DECIMAL(20,2) NOT NULL");
        DB::statement("ALTER TABLE merchant_wallet_records MODIFY total_balance DECIMAL(20,2) NOT NULL");
        DB::statement("ALTER TABLE merchant_wallet_records MODIFY available_balance DECIMAL(20,2) NOT NULL");
        DB::statement("ALTER TABLE merchant_wallet_records MODIFY hold_balance DECIMAL(20,2) NOT NULL");

        // agent_wallet_records
        DB::statement("ALTER TABLE agent_wallet_records MODIFY amount DECIMAL(20,2) NOT NULL");
        DB::statement("ALTER TABLE agent_wallet_records MODIFY pre_total_balance DECIMAL(20,2) NOT NULL");
        DB::statement("ALTER TABLE agent_wallet_records MODIFY pre_available_balance DECIMAL(20,2) NOT NULL");
        DB::statement("ALTER TABLE agent_wallet_records MODIFY pre_hold_balance DECIMAL(20,2) NOT NULL");
        DB::statement("ALTER TABLE agent_wallet_records MODIFY total_balance DECIMAL(20,2) NOT NULL");
        DB::statement("ALTER TABLE agent_wallet_records MODIFY available_balance DECIMAL(20,2) NOT NULL");
        DB::statement("ALTER TABLE agent_wallet_records MODIFY hold_balance DECIMAL(20,2) NOT NULL");

        // provider_wallet_records
        DB::statement("ALTER TABLE provider_wallet_records MODIFY amount DECIMAL(20,2) NOT NULL");
        DB::statement("ALTER TABLE provider_wallet_records MODIFY pre_total_balance DECIMAL(20,2) NOT NULL");
        DB::statement("ALTER TABLE provider_wallet_records MODIFY pre_available_balance DECIMAL(20,2) NOT NULL");
        DB::statement("ALTER TABLE provider_wallet_records MODIFY pre_hold_balance DECIMAL(20,2) NOT NULL");
        DB::statement("ALTER TABLE provider_wallet_records MODIFY total_balance DECIMAL(20,2) NOT NULL");
        DB::statement("ALTER TABLE provider_wallet_records MODIFY available_balance DECIMAL(20,2) NOT NULL");
        DB::statement("ALTER TABLE provider_wallet_records MODIFY hold_balance DECIMAL(20,2) NOT NULL");

        // merchant_payment_types
        DB::statement("ALTER TABLE merchant_payment_types MODIFY single_min_amount DECIMAL(20,2) NULL");
        DB::statement("ALTER TABLE merchant_payment_types MODIFY single_max_amount DECIMAL(20,2) NULL");
        DB::statement("ALTER TABLE merchant_payment_types MODIFY deposit_fee DECIMAL(20,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE merchant_payment_types MODIFY withdraw_fee DECIMAL(20,2) NOT NULL DEFAULT 0");

        // provider_payment_types
        DB::statement("ALTER TABLE provider_payment_types MODIFY single_min_amount DECIMAL(20,2) NULL");
        DB::statement("ALTER TABLE provider_payment_types MODIFY single_max_amount DECIMAL(20,2) NULL");
        DB::statement("ALTER TABLE provider_payment_types MODIFY daily_amount_limit DECIMAL(20,2) NULL");
        DB::statement("ALTER TABLE provider_payment_types MODIFY current_daily_amount DECIMAL(20,2) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE provider_payment_types MODIFY deposit_fee DECIMAL(20,2) NULL");
        DB::statement("ALTER TABLE provider_payment_types MODIFY withdraw_fee DECIMAL(20,2) NULL");
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            return;
        }

        // agents
        DB::statement("ALTER TABLE agents MODIFY total_balance DECIMAL(20,6) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE agents MODIFY available_balance DECIMAL(20,6) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE agents MODIFY hold_balance DECIMAL(20,6) NOT NULL DEFAULT 0");

        // merchants
        DB::statement("ALTER TABLE merchants MODIFY total_balance DECIMAL(20,6) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE merchants MODIFY available_balance DECIMAL(20,6) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE merchants MODIFY hold_balance DECIMAL(20,6) NOT NULL DEFAULT 0");

        // providers
        DB::statement("ALTER TABLE providers MODIFY total_balance DECIMAL(20,6) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE providers MODIFY available_balance DECIMAL(20,6) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE providers MODIFY hold_balance DECIMAL(20,6) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE providers MODIFY api_available_balance DECIMAL(20,6) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE providers MODIFY api_hold_balance DECIMAL(20,6) NOT NULL DEFAULT 0");

        // deposit_orders
        DB::statement("ALTER TABLE deposit_orders MODIFY order_amount DECIMAL(20,6) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE deposit_orders MODIFY actual_amount DECIMAL(20,6) NULL");
        DB::statement("ALTER TABLE deposit_orders MODIFY merchant_balance_change DECIMAL(20,6) NULL");
        DB::statement("ALTER TABLE deposit_orders MODIFY merchant_fee DECIMAL(20,6) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE deposit_orders MODIFY provider_fee DECIMAL(20,6) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE deposit_orders MODIFY agent_fee DECIMAL(20,6) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE deposit_orders MODIFY provider_agent_fee DECIMAL(20,6) NOT NULL DEFAULT 0");

        // withdraw_orders
        DB::statement("ALTER TABLE withdraw_orders MODIFY order_amount DECIMAL(20,6) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE withdraw_orders MODIFY actual_amount DECIMAL(20,6) NULL");
        DB::statement("ALTER TABLE withdraw_orders MODIFY merchant_fee DECIMAL(20,6) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE withdraw_orders MODIFY provider_fee DECIMAL(20,6) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE withdraw_orders MODIFY agent_fee DECIMAL(20,6) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE withdraw_orders MODIFY provider_agent_fee DECIMAL(20,6) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE withdraw_orders MODIFY total_debit DECIMAL(20,6) NOT NULL DEFAULT 0");

        // merchant_wallet_records
        DB::statement("ALTER TABLE merchant_wallet_records MODIFY amount DECIMAL(20,6) NOT NULL");
        DB::statement("ALTER TABLE merchant_wallet_records MODIFY pre_total_balance DECIMAL(20,6) NOT NULL");
        DB::statement("ALTER TABLE merchant_wallet_records MODIFY pre_available_balance DECIMAL(20,6) NOT NULL");
        DB::statement("ALTER TABLE merchant_wallet_records MODIFY pre_hold_balance DECIMAL(20,6) NOT NULL");
        DB::statement("ALTER TABLE merchant_wallet_records MODIFY total_balance DECIMAL(20,6) NOT NULL");
        DB::statement("ALTER TABLE merchant_wallet_records MODIFY available_balance DECIMAL(20,6) NOT NULL");
        DB::statement("ALTER TABLE merchant_wallet_records MODIFY hold_balance DECIMAL(20,6) NOT NULL");

        // agent_wallet_records
        DB::statement("ALTER TABLE agent_wallet_records MODIFY amount DECIMAL(20,6) NOT NULL");
        DB::statement("ALTER TABLE agent_wallet_records MODIFY pre_total_balance DECIMAL(20,6) NOT NULL");
        DB::statement("ALTER TABLE agent_wallet_records MODIFY pre_available_balance DECIMAL(20,6) NOT NULL");
        DB::statement("ALTER TABLE agent_wallet_records MODIFY pre_hold_balance DECIMAL(20,6) NOT NULL");
        DB::statement("ALTER TABLE agent_wallet_records MODIFY total_balance DECIMAL(20,6) NOT NULL");
        DB::statement("ALTER TABLE agent_wallet_records MODIFY available_balance DECIMAL(20,6) NOT NULL");
        DB::statement("ALTER TABLE agent_wallet_records MODIFY hold_balance DECIMAL(20,6) NOT NULL");

        // provider_wallet_records
        DB::statement("ALTER TABLE provider_wallet_records MODIFY amount DECIMAL(20,6) NOT NULL");
        DB::statement("ALTER TABLE provider_wallet_records MODIFY pre_total_balance DECIMAL(20,6) NOT NULL");
        DB::statement("ALTER TABLE provider_wallet_records MODIFY pre_available_balance DECIMAL(20,6) NOT NULL");
        DB::statement("ALTER TABLE provider_wallet_records MODIFY pre_hold_balance DECIMAL(20,6) NOT NULL");
        DB::statement("ALTER TABLE provider_wallet_records MODIFY total_balance DECIMAL(20,6) NOT NULL");
        DB::statement("ALTER TABLE provider_wallet_records MODIFY available_balance DECIMAL(20,6) NOT NULL");
        DB::statement("ALTER TABLE provider_wallet_records MODIFY hold_balance DECIMAL(20,6) NOT NULL");

        // merchant_payment_types
        DB::statement("ALTER TABLE merchant_payment_types MODIFY single_min_amount DECIMAL(20,6) NULL");
        DB::statement("ALTER TABLE merchant_payment_types MODIFY single_max_amount DECIMAL(20,6) NULL");
        DB::statement("ALTER TABLE merchant_payment_types MODIFY deposit_fee DECIMAL(20,6) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE merchant_payment_types MODIFY withdraw_fee DECIMAL(20,6) NOT NULL DEFAULT 0");

        // provider_payment_types
        DB::statement("ALTER TABLE provider_payment_types MODIFY single_min_amount DECIMAL(20,6) NULL");
        DB::statement("ALTER TABLE provider_payment_types MODIFY single_max_amount DECIMAL(20,6) NULL");
        DB::statement("ALTER TABLE provider_payment_types MODIFY daily_amount_limit DECIMAL(20,6) NULL");
        DB::statement("ALTER TABLE provider_payment_types MODIFY current_daily_amount DECIMAL(20,6) NOT NULL DEFAULT 0");
        DB::statement("ALTER TABLE provider_payment_types MODIFY deposit_fee DECIMAL(20,6) NULL");
        DB::statement("ALTER TABLE provider_payment_types MODIFY withdraw_fee DECIMAL(20,6) NULL");
    }
};
