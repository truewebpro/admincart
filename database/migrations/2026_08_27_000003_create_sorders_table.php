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
        Schema::create('sorders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();

            $table->string('thirdparty_id'); // Shopify's order id
            $table->boolean('is_pinned')->default(false);
            $table->string('order_number')->nullable();
            $table->string('email')->nullable();
            $table->string('financial_status')->nullable();
            $table->string('fulfillment_status')->nullable();

            $table->decimal('subtotal_price', 10, 2)->default(0);
            $table->decimal('total_discounts', 10, 2)->default(0);
            $table->decimal('total_tax', 10, 2)->default(0);
            $table->decimal('total_shipping', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2)->default(0);
            $table->string('currency', 10)->nullable();

            $table->string('customer_thirdparty_id')->nullable();
            $table->string('customer_name')->nullable(); // denormalized for quick display, no join needed for the reference table

            $table->json('line_items')->nullable();
            $table->json('shipping_address')->nullable();

            $table->timestamp('shopify_created_at')->nullable();

            $table->timestamps();

            $table->unique(['shop_id', 'thirdparty_id']);
            $table->index(['shop_id', 'shopify_created_at']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sorders');
    }
};
