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
        Schema::create('coupon_usages', function (Blueprint $table) {
            $table->id('coupon_usage_id')->index();
            $table->foreignId('coupon_id')
                ->constrained('coupons','coupon_id')
                ->onDelete('cascade');
            $table->foreignId('customer_id')
                ->constrained('customers','customer_id')
                ->onDelete('cascade');
            $table->integer('times_used')->default(0); // increment this per use
            $table->timestamps();
            $table->unique(['coupon_id', 'customer_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupon_usages');
    }
};
