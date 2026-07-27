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
        Schema::create('active_visitors', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();
            $table->string('session_id');
            $table->foreignId('customer_id')->nullable()->constrained('customers', 'customer_id')->nullOnDelete();
            $table->string('current_path', 500)->nullable();
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->unique(['shop_id', 'session_id']); // one row per active session, upserted repeatedly
            $table->index(['shop_id', 'last_seen_at']); // this is THE query for "how many live right now"
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('active_visitors');
    }
};
