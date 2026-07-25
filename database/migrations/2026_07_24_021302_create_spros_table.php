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

        Schema::create('spros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();

            $table->unsignedBigInteger('shopify_product_id'); // Shopify's product id
            $table->string('title')->nullable();
            $table->string('handle')->nullable();
            $table->string('vendor')->nullable();
            $table->string('product_type')->nullable();
            $table->string('status')->nullable();

            $table->longText('body_html')->nullable(); // product description HTML
            $table->text('tags')->nullable();

            $table->json('variants')->nullable();
            $table->json('options')->nullable();
            $table->json('images')->nullable();   // full images array as returned by Shopify
            $table->text('image_one')->nullable(); // first/featured image src, quick access without decoding json

            // Local import tracking: null = not imported yet.
            $table->foreignId('product_id')->nullable()->constrained('products','product_id')->nullOnDelete();
            $table->timestamp('imported_at')->nullable();

            $table->timestamps();

            $table->unique(['shop_id', 'shopify_product_id']); // one staging row per shop per Shopify product
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('spros');
    }
};
