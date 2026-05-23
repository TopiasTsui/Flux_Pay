<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('deposit_orders', function (Blueprint $table) {
            $table->dropIndex('idx_deposit_merchant_order');
            $table->unique(['merchant_id', 'merchant_order_no'], 'uk_deposit_merchant_order');
        });

        Schema::table('withdraw_orders', function (Blueprint $table) {
            $table->dropIndex('idx_withdraw_merchant_order');
            $table->unique(['merchant_id', 'merchant_order_no'], 'uk_withdraw_merchant_order');
        });
    }

    public function down(): void
    {
        Schema::table('deposit_orders', function (Blueprint $table) {
            $table->dropUnique('uk_deposit_merchant_order');
            $table->index(['merchant_id', 'merchant_order_no'], 'idx_deposit_merchant_order');
        });

        Schema::table('withdraw_orders', function (Blueprint $table) {
            $table->dropUnique('uk_withdraw_merchant_order');
            $table->index(['merchant_id', 'merchant_order_no'], 'idx_withdraw_merchant_order');
        });
    }
};
