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
        Schema::create('acarts', function (Blueprint $table) {
            $table->id('acart_id')->index();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id');
            $table->string('cart_token');
            $table->foreignId('customer_id')->nullable()->constrained('customers', 'customer_id');
            $table->foreignId('order_id')->nullable()->constrained('orders', 'order_id');
            $table->boolean('is_active')->default(true);
            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('platform')->nullable();
            $table->string('currency')->default('GBP');
            $table->string('cart_status')->default('active');
            $table->unsignedInteger('cart_version')->default(1);
            $table->integer('items_count')->default(0);
            $table->decimal('subtotal',10,2)->default(0);
            $table->decimal('discount_amount',10,2)->default(0);
            $table->decimal('shipping_amount',10,2)->default(0);
            $table->decimal('tax_amount',10,2)->default(0);
            $table->decimal('cart_total',10,2)->default(0);
            $table->timestamp('last_activity_at')->nullable()->index();
            $table->string('checkout_id')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('shipping_method')->nullable();
            $table->decimal('shipping_cost',10,2)->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->unique(['shop_id', 'cart_token']);
            $table->index(['shop_id','is_active']);
            $table->index(['shop_id','cart_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acarts');
    }
};
