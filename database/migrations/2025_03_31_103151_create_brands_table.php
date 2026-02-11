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
        Schema::create('brands', function (Blueprint $table) {
            $table->id('brand_id')->index();
            $table->string('brand_name');
            $table->string('brand_slug');
            $table->longText('brand_desc')->nullable();
            $table->enum('brand_status', ['Active', 'Inactive'])->default('Active');
            $table->string('brand_image')->nullable();
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
        Schema::dropIfExists('brands');
    }
};
