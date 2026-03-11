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
        Schema::create('acart_discounts', function (Blueprint $table) {
            $table->id('acart_discount_id')->index();
            $table->foreignId('acart_id')->constrained('acarts','acart_id')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
            $table->string('discount_code')->nullable()->index();
            // coupon | automatic | shipping | loyalty | custom
            $table->string('discount_source')->default('coupon');
            $table->enum('discount_type', [
                'percentage',
                'fixed_cart',
                'fixed_product',
                'free_shipping'
            ]);
            $table->decimal('discount_value',10,2)->default(0);
            $table->decimal('applied_amount',10,2)->default(0);
            $table->boolean('is_stackable')->default(false);
            $table->json('meta')->nullable();
            $table->enum('discount_status',['active','removed'])->default('active');
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['acart_id','discount_status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acart_discounts');
    }
};
