<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agent_id')->constrained('agents');
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('md5key', 64);
            $table->string('currency_code', 10)->default('PHP');
            $table->tinyInteger('status')->default(1);
            $table->decimal('total_balance', 20, 6)->default(0);
            $table->decimal('available_balance', 20, 6)->default(0);
            $table->decimal('hold_balance', 20, 6)->default(0);
            $table->json('white_ips')->nullable();
            $table->json('options')->nullable();
            $table->timestamps();

            $table->index('agent_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
