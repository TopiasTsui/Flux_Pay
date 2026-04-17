<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->string('merchant_order_no', 100);
            $table->string('system_order_no', 50)->unique();
            $table->unsignedBigInteger('provider_payment_type_id')->nullable();
            $table->string('provider_order_no', 100)->nullable();
            $table->string('provider_order_detail_no', 100)->nullable();
            $table->decimal('order_amount', 20, 6)->default(0);
            $table->decimal('actual_amount', 20, 6)->nullable();
            $table->decimal('merchant_balance_change', 20, 6)->nullable();
            $table->decimal('merchant_fee', 20, 6)->default(0);
            $table->decimal('provider_fee', 20, 6)->default(0);
            $table->decimal('agent_fee', 20, 6)->default(0);
            $table->json('agent_fee_map')->nullable();
            $table->decimal('provider_agent_fee', 20, 6)->default(0);
            $table->json('provider_agent_fee_map')->nullable();
            $table->string('bank_code', 50)->nullable();
            $table->string('payer_name', 100)->nullable();
            $table->string('sender_account_number', 100)->nullable();
            $table->string('sender_account_name', 100)->nullable();
            $table->string('currency', 10)->default('PHP');
            $table->tinyInteger('status')->default(0);
            $table->tinyInteger('callback_status')->default(0);
            $table->tinyInteger('fund_status')->default(0);
            $table->timestamp('fund_at')->nullable();
            $table->string('merchant_notify_url', 500)->nullable();
            $table->text('merchant_extra')->nullable();
            $table->timestamp('provider_apply_time')->nullable();
            $table->timestamp('provider_callback_time')->nullable();
            $table->unsignedBigInteger('failed_handler_id')->nullable();
            $table->timestamp('failed_handle_time')->nullable();
            $table->text('remark')->nullable();
            $table->timestamps();

            $table->index(['merchant_id', 'merchant_order_no'], 'idx_deposit_merchant_order');
            $table->index('status', 'idx_deposit_status');
            $table->index('fund_status', 'idx_deposit_fund_status');
            $table->index('created_at', 'idx_deposit_created_at');
            $table->index('provider_payment_type_id', 'idx_deposit_ppt_id');
            $table->foreign('provider_payment_type_id')->references('id')->on('provider_payment_types')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_orders');
    }
};
