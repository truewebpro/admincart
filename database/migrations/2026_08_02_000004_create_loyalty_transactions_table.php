<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();

            // The real relation: points are scoped per shop via customer_shops.
            $table->foreignId('cshop_id')->constrained('customer_shops', 'cshop_id')->cascadeOnDelete();

            // Denormalized for convenience — lets you query "everything this customer
            // earned across shops" without joining through customer_shops each time.
            $table->foreignId('customer_id')->constrained('customers', 'customer_id')->cascadeOnDelete();

            $table->enum('type', ['earn', 'redeem', 'adjustment', 'expired']);

            // Positive for earn/adjustment(+), negative for redeem/adjustment(-)/expired
            $table->integer('points');
            $table->unsignedInteger('balance_after'); // customer_shops points balance snapshot after this txn

            $table->foreignId('order_id')->nullable()->constrained('orders','order_id')->nullOnDelete();
            $table->foreignId('loyalty_redeem_rule_id')->nullable()
                ->constrained('loyalty_redeem_rules')->nullOnDelete();

            $table->text('notes')->nullable();

            $table->enum('created_by_type', ['admin', 'system', 'customer']);
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['shop_id', 'cshop_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_transactions');
    }
};
