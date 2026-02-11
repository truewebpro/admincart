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
        Schema::create('featuredcats', function (Blueprint $table) {
            $table->id('fcat_id')->index();
            $table->string('heading');
            $table->string('subheading')->nullable();
            $table->string('promo_text')->nullable();
            $table->string('label_text')->default('New');
            $table->enum('type', ['New', 'Deal'])->default('New');
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->string('image_url')->nullable();
            $table->foreignId('cat_id')->constrained('cats','cat_id')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('featuredcats');
    }
};
