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
        Schema::create('catpros', function (Blueprint $table) {
            $table->foreignId('cat_id')->constrained('cats','cat_id')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products','product_id')->cascadeOnDelete();
            $table->integer('position')->default(99);
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
            $table->unique(['cat_id', 'product_id', 'shop_id']);
            $table->index(['shop_id', 'cat_id']);
            $table->index(['product_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catpros');
    }
};
