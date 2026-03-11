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
        Schema::create('acart_items', function (Blueprint $table) {
            $table->id('acart_item_id')->index();
            $table->foreignId('acart_id')->constrained('acarts','acart_id')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products','product_id');
            $table->foreignId('variant_id')->constrained('variants','variant_id');
            $table->string('title')->nullable();
            $table->json('options_json')->nullable();
            $table->decimal('price',10,2);
            $table->integer('quantity')->default(1);
            $table->decimal('line_total',10,2)->default(0);
            $table->timestamps();
            $table->unique(['acart_id','variant_id']);
            $table->index('acart_id');
            $table->index('shop_id');
            $table->index('variant_id');
            $table->index(['shop_id','product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acart_items');
    }
};
