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
        Schema::create('order_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')
                ->constrained('orders','order_id')
                ->onDelete('cascade');

            $table->foreignId('coupon_id')
                ->nullable()
                ->constrained('coupons','coupon_id')
                ->onDelete('set null');

            $table->string('coupon_code');
            $table->decimal('discount_amount', 10, 2);

            $table->string('type')->nullable(); // fixed / percentage
            $table->decimal('value', 10, 2)->nullable();
            $table->integer('priority')->nullable();

            $table->foreignId('shop_id')
                ->constrained('shops','shop_id')
                ->onDelete('cascade');

            $table->timestamps();
            $table->index('order_id');
            $table->index('shop_id');
            $table->index(['shop_id', 'order_id']);
            $table->unique(['order_id', 'coupon_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_coupons');
    }
};
