<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->string('types', 20)->default('merchant')->comment('merchant or provider');
            $table->string('name', 100);
            $table->tinyInteger('level')->unsigned()->default(1)->comment('1/2/3');
            $table->tinyInteger('status')->default(1)->comment('0=inactive,1=active');
            $table->string('currency', 10)->default('PHP');
            $table->decimal('total_balance', 20, 6)->default(0);
            $table->decimal('available_balance', 20, 6)->default(0);
            $table->decimal('hold_balance', 20, 6)->default(0);
            $table->timestamps();

            $table->index('parent_id');
            $table->index(['types', 'status']);
            $table->foreign('parent_id')->references('id')->on('agents')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agents');
    }
};
