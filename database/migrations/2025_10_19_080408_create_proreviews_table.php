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
        Schema::create('proreviews', function (Blueprint $table) {
            $table->id('proreview_id');
            $table->string('review_title');
            $table->string('review_text')->nullable();
            $table->enum('review_status', ['pending', 'verified','rejected'])->default('pending');
            $table->float('rating')->default(5);
            $table->foreignId('reviewer_id')->constrained('reviewers','id')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products','product_id')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('proreviews');
    }
};
