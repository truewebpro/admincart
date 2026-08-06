<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('store_credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();
            $table->foreignId('cshop_id')->constrained('customer_shops', 'cshop_id')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers', 'customer_id')->cascadeOnDelete(); // denormalized

            // credit = adds to balance, debit = removes from balance
            $table->enum('type', ['credit', 'debit']);

            // Where this transaction came from, for reporting / filtering in admin
            $table->enum('source', [
                'manual_admin',        // admin manually adjusted balance
                'order_refund_partial',// partial refund issued as credit instead of cash
                'order_refund_full',   // full refund issued as credit instead of cash
                'loyalty_redeem',      // customer redeemed loyalty points
                'checkout_usage',      // customer spent credit at checkout
                'expired',             // credit expired (if you enable expiry later)
                'other',
            ]);

            $table->decimal('amount', 12, 2); // always positive; `type` gives direction
            $table->decimal('balance_after', 12, 2); // snapshot of customer_shops balance after this txn

            $table->foreignId('order_id')->nullable()->constrained('orders','order_id')->nullOnDelete();
            $table->foreignId('loyalty_transaction_id')->nullable()
                ->constrained('loyalty_transactions')->nullOnDelete();

            $table->text('notes')->nullable();

            // Who performed the action
            $table->enum('created_by_type', ['admin', 'system', 'customer']);
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['shop_id', 'cshop_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_credit_transactions');
    }
};
