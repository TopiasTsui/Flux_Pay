<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 50)->unique()->after('id');
            $table->string('organization', 100)->nullable()->after('name');
            $table->text('notes')->nullable()->after('organization');
            $table->boolean('is_active')->default(true)->after('notes');
        });

        // Make email nullable using raw SQL to avoid doctrine/dbal dependency
        DB::statement("ALTER TABLE users MODIFY email VARCHAR(255) NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL");

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'organization', 'notes', 'is_active']);
        });
    }
};
