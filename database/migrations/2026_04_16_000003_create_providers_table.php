<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('providers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->string('name', 100);
            $table->string('provider_no', 100)->nullable();
            $table->string('vendor_id', 50)->nullable();
            $table->json('vendor_meta')->nullable();
            $table->string('bank_config_key', 50)->nullable();
            $table->string('currency_code', 10)->default('PHP');
            $table->tinyInteger('status')->default(1);
            $table->decimal('total_balance', 20, 6)->default(0);
            $table->decimal('available_balance', 20, 6)->default(0);
            $table->decimal('hold_balance', 20, 6)->default(0);
            $table->decimal('api_available_balance', 20, 6)->default(0);
            $table->decimal('api_hold_balance', 20, 6)->default(0);
            $table->text('call_back_ips')->nullable();
            $table->json('options')->nullable();
            $table->timestamps();

            $table->index('vendor_id');
            $table->index('agent_id');
            $table->foreign('agent_id')->references('id')->on('agents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('providers');
    }
};
