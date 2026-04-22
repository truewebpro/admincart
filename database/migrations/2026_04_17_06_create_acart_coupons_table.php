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
        Schema::create('acart_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acart_id')
                ->constrained('acarts','acart_id')
                ->onDelete('cascade');

            $table->foreignId('coupon_id')
                ->nullable()
                ->constrained('coupons','coupon_id')
                ->onDelete('set null');

            $table->string('coupon_code');
            $table->decimal('discount_amount', 10, 2)->default(0);

            $table->string('type')->nullable();
            $table->decimal('value', 10, 2)->nullable();
            $table->integer('priority')->nullable();

            $table->foreignId('shop_id')
                ->constrained('shops','shop_id')
                ->onDelete('cascade');

            $table->timestamps();
            $table->index('acart_id');
            $table->index('shop_id');
            $table->index(['shop_id', 'acart_id']);
            $table->unique(['acart_id', 'coupon_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acart_coupons');
    }
};
