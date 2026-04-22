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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id('coupon_id')->index();
            $table->foreignId('shop_id')
                ->constrained('shops','shop_id')
                ->onDelete('cascade');
            $table->string('code');
            $table->enum('type', ['fixed', 'percentage', 'bogo', 'bundle'])->default('fixed');
            $table->decimal('value',10,2);
            $table->enum('applies_to', ['entire_order', 'products', 'cats'])->default('entire_order');
            $table->decimal('min_order_amount',10,2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->integer('usage_limit')->nullable(); // total uses
            $table->integer('per_customer_limit')->nullable();
            $table->boolean('is_stackable')->default(false);
            $table->integer('priority')->default(0);
            $table->json('conditions')->nullable(); // Optional advanced conditions
            $table->timestamps();

            $table->unique(['shop_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
