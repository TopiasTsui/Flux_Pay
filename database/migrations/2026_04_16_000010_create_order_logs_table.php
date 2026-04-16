<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_logs', function (Blueprint $table) {
            $table->id();
            $table->string('orderable_type', 100);
            $table->unsignedBigInteger('orderable_id');
            $table->string('action', 50);
            $table->json('request_data')->nullable();
            $table->json('response_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('remark')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['orderable_type', 'orderable_id'], 'idx_orderable');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_logs');
    }
};
