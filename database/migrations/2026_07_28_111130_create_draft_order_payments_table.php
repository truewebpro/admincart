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
        Schema::create('draft_order_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
            $table->foreignId('draft_order_id')->constrained('draft_orders')->cascadeOnDelete();

            $table->decimal('amount', 12, 2);
            $table->string('payment_method', 20); // cash | card | bank_transfer | customer_credit | other
            $table->string('payment_reference')->nullable();
            $table->string('payment_status', 20)->default('pending'); // pending | completed | refunded

            $table->timestamp('paid_at')->nullable();
            $table->foreignId('recorded_by')->constrained('users');
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['shop_id', 'draft_order_id']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_order_payments');
    }
};
