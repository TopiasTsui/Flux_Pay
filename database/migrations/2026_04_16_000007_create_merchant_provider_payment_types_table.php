<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchant_provider_payment_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('merchant_id')->constrained('merchants');
            $table->foreignId('provider_payment_type_id')->constrained('provider_payment_types');
            $table->tinyInteger('status')->default(1);
            $table->string('remark', 255)->nullable();
            $table->timestamps();

            $table->unique(['merchant_id', 'provider_payment_type_id'], 'uk_merchant_ppt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchant_provider_payment_types');
    }
};
