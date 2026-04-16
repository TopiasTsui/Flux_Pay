<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_transaction_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('deposit_count')->default(0);
            $table->decimal('deposit_amount', 20, 6)->default(0);
            $table->integer('deposit_success_count')->default(0);
            $table->decimal('deposit_success_amount', 20, 6)->default(0);
            $table->integer('withdraw_count')->default(0);
            $table->decimal('withdraw_amount', 20, 6)->default(0);
            $table->integer('withdraw_success_count')->default(0);
            $table->decimal('withdraw_success_amount', 20, 6)->default(0);
            $table->timestamps();

            $table->unique('date');
        });

        Schema::create('daily_transaction_stats_by_merchant', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('merchant_id');
            $table->integer('deposit_count')->default(0);
            $table->decimal('deposit_amount', 20, 6)->default(0);
            $table->integer('deposit_success_count')->default(0);
            $table->decimal('deposit_success_amount', 20, 6)->default(0);
            $table->integer('withdraw_count')->default(0);
            $table->decimal('withdraw_amount', 20, 6)->default(0);
            $table->integer('withdraw_success_count')->default(0);
            $table->decimal('withdraw_success_amount', 20, 6)->default(0);
            $table->timestamps();

            $table->unique(['date', 'merchant_id']);
        });

        Schema::create('daily_transaction_stats_by_provider', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('provider_id');
            $table->integer('deposit_count')->default(0);
            $table->decimal('deposit_amount', 20, 6)->default(0);
            $table->integer('deposit_success_count')->default(0);
            $table->decimal('deposit_success_amount', 20, 6)->default(0);
            $table->integer('withdraw_count')->default(0);
            $table->decimal('withdraw_amount', 20, 6)->default(0);
            $table->integer('withdraw_success_count')->default(0);
            $table->decimal('withdraw_success_amount', 20, 6)->default(0);
            $table->timestamps();

            $table->unique(['date', 'provider_id']);
        });

        Schema::create('daily_revenue_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->decimal('total_revenue', 20, 6)->default(0);
            $table->decimal('merchant_fees', 20, 6)->default(0);
            $table->decimal('provider_fees', 20, 6)->default(0);
            $table->decimal('agent_commissions', 20, 6)->default(0);
            $table->decimal('net_profit', 20, 6)->default(0);
            $table->timestamps();

            $table->unique('date');
        });

        Schema::create('daily_revenue_stats_by_merchant', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('merchant_id');
            $table->decimal('merchant_fees', 20, 6)->default(0);
            $table->decimal('agent_commissions', 20, 6)->default(0);
            $table->timestamps();

            $table->unique(['date', 'merchant_id']);
        });

        Schema::create('daily_revenue_stats_by_provider', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedBigInteger('provider_id');
            $table->decimal('provider_fees', 20, 6)->default(0);
            $table->decimal('provider_agent_commissions', 20, 6)->default(0);
            $table->timestamps();

            $table->unique(['date', 'provider_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_revenue_stats_by_provider');
        Schema::dropIfExists('daily_revenue_stats_by_merchant');
        Schema::dropIfExists('daily_revenue_stats');
        Schema::dropIfExists('daily_transaction_stats_by_provider');
        Schema::dropIfExists('daily_transaction_stats_by_merchant');
        Schema::dropIfExists('daily_transaction_stats');
    }
};
