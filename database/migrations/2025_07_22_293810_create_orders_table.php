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
        Schema::create('orders', function (Blueprint $table) {
            $table->id('order_id')->index();
            $table->string('order_number')->unique()->nullable(); // fill via model observer or logic
            $table->foreignId('shop_id')->constrained('shops','shop_id');
            $table->foreignId('customer_id')->constrained('customers','customer_id');
            $table->foreignId('address_id')->nullable()
                ->constrained('customer_addresses', 'address_id')->onDelete('set null');
            $table->enum('order_status', ['pending', 'processing', 'completed', 'archived'])->default('pending');
            $table->string('payment_method')->default('Bank Transfer'); // e.g. stripe, cod
            $table->enum('payment_status', ['pending','paid','partially_paid','refunded','partially_refunded','unpaid','expired','voided'])->default('pending');
            $table->enum('fulfillment_status', ['unfulfilled','picking', 'picked', 'packing', 'packed','partially_fulfilled','fulfilled','scheduled','on_hold','request_declined'])->default('unfulfilled');
            $table->string('shipping_method')->default('Standard Delivery');
            $table->float('shipping_cost')->default(0);
            $table->foreignId('coupon_id')->nullable()->constrained('coupons','coupon_id')->onDelete('set null');
            $table->string('coupon_code')->nullable();
            $table->float('discount_amount')->default(0);
            $table->float('subtotal')->default(0);
            $table->float('order_total');
            $table->float('tax_amount')->default(0);
            $table->string('currency_code')->default('GBP');
            $table->boolean('is_guest_order')->default(false);
            $table->string('shipping_name');
            $table->string('shipping_phone')->nullable();
            $table->string('shipping_address_line1');
            $table->string('shipping_address_line2')->nullable();
            $table->string('shipping_city');
            $table->string('shipping_postcode');
            $table->string('shipping_country')->default('GB');
            $table->string('notes')->nullable();
            $table->string('checkout_id')->nullable();
            $table->timestamp('placed_at')->nullable(); // Optional, for tracking
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
