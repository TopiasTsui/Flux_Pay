<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_payment_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->foreignId('payment_type_id')->constrained('payment_types');
            $table->tinyInteger('status')->default(1);
            $table->decimal('single_min_amount', 20, 2)->default(0);
            $table->decimal('single_max_amount', 20, 2)->default(999999);
            $table->tinyInteger('deposit_fee_type')->default(1);
            $table->decimal('deposit_fee', 10, 4)->default(0);
            $table->json('deposit_agents_fee')->nullable();
            $table->tinyInteger('withdraw_fee_type')->default(1);
            $table->decimal('withdraw_fee', 10, 4)->default(0);
            $table->json('withdraw_agents_fee')->nullable();
            $table->timestamps();

            $table->unique(['merchant_id', 'payment_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_payment_types');
    }
};
