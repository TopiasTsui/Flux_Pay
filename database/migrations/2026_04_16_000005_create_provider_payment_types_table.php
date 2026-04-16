<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('provider_payment_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('providers');
            $table->foreignId('payment_type_id')->constrained('payment_types');
            $table->string('type', 20)->comment('deposit or withdraw');
            $table->string('alias', 100)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->tinyInteger('weight')->unsigned()->default(50)->comment('1-100');
            $table->decimal('single_min_amount', 20, 2)->default(0);
            $table->decimal('single_max_amount', 20, 2)->default(999999);
            $table->decimal('daily_amount_limit', 20, 2)->default(0);
            $table->integer('daily_count_limit')->default(0);
            $table->decimal('current_daily_amount', 20, 2)->default(0);
            $table->string('reset_time', 5)->default('00:00');
            $table->tinyInteger('deposit_fee_type')->default(1)->comment('1=percentage,2=fixed');
            $table->decimal('deposit_fee', 10, 4)->default(0);
            $table->tinyInteger('withdraw_fee_type')->default(1);
            $table->decimal('withdraw_fee', 10, 4)->default(0);
            $table->decimal('agent_fee', 10, 4)->default(0);
            $table->timestamps();

            $table->index(['provider_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_payment_types');
    }
};
