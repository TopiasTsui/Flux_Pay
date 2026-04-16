<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_menus', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('title', 100);
            $table->string('slug', 50)->unique();
            $table->string('icon', 50)->nullable();
            $table->string('route', 100)->nullable()->comment('Orchid route name');
            $table->string('url', 255)->nullable()->comment('External URL, overrides route');
            $table->string('permission', 100)->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('parent_id');
            $table->index('sort_order');
        });

        Schema::dropIfExists('proxies');
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_menus');

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
    }
};
