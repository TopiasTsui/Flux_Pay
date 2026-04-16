<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->createWalletTable('merchant_wallet_records', 'merchant_id', 'merchants');
        $this->createWalletTable('agent_wallet_records', 'agent_id', 'agents');
        $this->createWalletTable('provider_wallet_records', 'provider_id', 'providers');
    }

    private function createWalletTable(string $tableName, string $fkColumn, string $fkTable): void
    {
        Schema::create($tableName, function (Blueprint $table) use ($fkColumn, $fkTable) {
            $table->id();
            $table->foreignId($fkColumn)->constrained($fkTable);
            $table->string('sn', 50)->unique();
            $table->string('type_code', 50);
            $table->decimal('amount', 20, 6);
            $table->decimal('pre_total_balance', 20, 6);
            $table->decimal('pre_available_balance', 20, 6);
            $table->decimal('pre_hold_balance', 20, 6);
            $table->decimal('total_balance', 20, 6);
            $table->decimal('available_balance', 20, 6);
            $table->decimal('hold_balance', 20, 6);
            $table->string('system_order_no', 50)->nullable();
            $table->string('related_type', 100)->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->text('remark')->nullable();
            $table->text('remark_view')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index($fkColumn);
            $table->index('type_code');
            $table->index('system_order_no');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('provider_wallet_records');
        Schema::dropIfExists('agent_wallet_records');
        Schema::dropIfExists('merchant_wallet_records');
    }
};
