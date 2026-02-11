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
        Schema::create('cats', function (Blueprint $table) {
            $table->id('cat_id')->index();
            $table->string('cat_name');
            $table->string('cat_slug');
            $table->longText('cat_desc')->nullable();
            $table->enum('cat_status', ['Active', 'Inactive'])->default('Active');
            $table->string('cat_image')->nullable();
            $table->enum('cat_type',['manual','smart'])->default('manual');
            $table->enum('cat_rule',['or','and'])->default('or');
            $table->enum('sort_order',['title_asc','title_desc','price_asc','price_desc','created_asc','created_desc'])->default('title_asc');
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
            $table->string('meta_title')->nullable();
            $table->string('meta_desc')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cats');
    }
};
