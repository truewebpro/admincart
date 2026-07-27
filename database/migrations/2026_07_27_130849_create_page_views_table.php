<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('page_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();
            $table->string('session_id'); // anonymous visitor id, generated client-side, stored in cookie/localStorage
            $table->foreignId('customer_id')->nullable()->constrained('customers', 'customer_id')->nullOnDelete();
            $table->string('path', 500);
            $table->string('referrer', 500)->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('browser', 100)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['shop_id', 'created_at']);
            $table->index(['shop_id', 'path']);
            $table->index('session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_views');
    }
};
