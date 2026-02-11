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
        Schema::create('variants', function (Blueprint $table) {
            $table->id('variant_id')->index();
            $table->string('sku');
            $table->float('price');
            $table->float('compareprice')->nullable();
            $table->float('costprice')->nullable();
            $table->string('barcode')->nullable();
            $table->string('variant_image')->nullable();
            $table->boolean('istax')->default(0);
            $table->boolean('isdefault')->default(0);
            $table->float('weight')->nullable();
            $table->json('options')->nullable();
            $table->json('option_values')->nullable();
            $table->timestamps();
            $table->foreignId('product_id')->constrained('products','product_id')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('variants');
    }
};
