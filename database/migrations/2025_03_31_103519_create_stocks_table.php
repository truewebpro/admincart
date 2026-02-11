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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id('stock_id')->index();
            $table->integer('quantity')->default(0);
            $table->foreignId('location_id')->constrained('locations','location_id')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('variants','variant_id')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products','product_id')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};
