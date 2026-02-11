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
        Schema::create('products', function (Blueprint $table) {
            $table->id('product_id')->index();
            $table->string('title');
            $table->string('handle');
            $table->enum('publish_status',['online','app'])->default('online');
            $table->longText('body_html')->nullable();
            $table->string('featured_image')->nullable();
            $table->enum('product_status',['Active','Draft'])->default('Draft');
            $table->foreignId('product_type_id')->constrained('product_types','product_type_id')->cascadeOnDelete();
            $table->foreignId('brand_id')->constrained('brands','brand_id')->cascadeOnDelete();
            $table->json('tags')->nullable();
            $table->string('thirdparty_id')->nullable();
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
            $table->timestamps();
            $table->string('meta_title')->nullable();
            $table->string('meta_desc')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
