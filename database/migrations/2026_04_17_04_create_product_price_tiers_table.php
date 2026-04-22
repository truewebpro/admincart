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
        Schema::create('product_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')
                ->constrained('products','product_id')
                ->onDelete('cascade');
            $table->foreignId('shop_id')
                ->constrained('shops','shop_id')
                ->onDelete('cascade');
            $table->integer('min_qty'); // 1, 5, 10
            $table->decimal('price', 10, 2);
            $table->enum('pricing_type', ['fixed', 'percentage'])->default('fixed');
            $table->timestamps();

            $table->unique(['product_id', 'min_qty']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_price_tiers');
    }
};
