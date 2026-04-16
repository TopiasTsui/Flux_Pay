<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('agent_id')->nullable()->after('id');
            $table->unsignedBigInteger('merchant_id')->nullable()->after('agent_id');
            $table->boolean('google_two_fa_enable')->default(false)->after('remember_token');
            $table->string('google_two_fa_secret')->nullable()->after('google_two_fa_enable');

            $table->index('agent_id');
            $table->index('merchant_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['agent_id']);
            $table->dropIndex(['merchant_id']);
            $table->dropColumn(['agent_id', 'merchant_id', 'google_two_fa_enable', 'google_two_fa_secret']);
        });
    }
};
