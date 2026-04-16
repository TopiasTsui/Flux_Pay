<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->string('bank_code', 50)->unique();
            $table->string('name', 100);
            $table->tinyInteger('status')->default(1);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('provider_bank_codes', function (Blueprint $table) {
            $table->id();
            $table->string('bank_config_key', 50);
            $table->string('bank_code', 50);
            $table->string('provider_bank_code', 100);
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->index('bank_config_key');
        });

        Schema::create('blacklists', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->string('value', 255);
            $table->string('remark', 255)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->index(['type', 'status']);
        });

        Schema::create('proxies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('host', 255);
            $table->integer('port')->default(0);
            $table->string('username', 100)->nullable();
            $table->string('password', 100)->nullable();
            $table->string('protocol', 20)->default('http');
            $table->tinyInteger('status')->default(1);
            $table->integer('priority')->default(0);
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('system_configs', function (Blueprint $table) {
            $table->id();
            $table->string('group', 50)->default('general');
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('remark', 255)->nullable();
            $table->timestamps();
        });

        Schema::create('admin_user_ip_whitelists', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_user_id');
            $table->string('ip_address', 45);
            $table->string('remark', 255)->nullable();
            $table->tinyInteger('status')->default(1);
            $table->timestamps();

            $table->index('admin_user_id');
        });

        Schema::create('login_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_user_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('admin_user_id');
        });

        Schema::create('operation_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('admin_user_id')->nullable();
            $table->string('module', 50)->nullable();
            $table->string('action', 50)->nullable();
            $table->string('target_type', 100)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->text('description')->nullable();
            $table->json('payload')->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index('admin_user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operation_logs');
        Schema::dropIfExists('login_logs');
        Schema::dropIfExists('admin_user_ip_whitelists');
        Schema::dropIfExists('system_configs');
        Schema::dropIfExists('proxies');
        Schema::dropIfExists('blacklists');
        Schema::dropIfExists('provider_bank_codes');
        Schema::dropIfExists('banks');
    }
};
