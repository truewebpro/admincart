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
        Schema::create('comments', function (Blueprint $table) {
            $table->id('comment_id')->index();
            $table->foreignId('blog_id')->constrained('blogs','blog_id')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers','customer_id')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
            $table->text('detail');
            $table->boolean('is_approved')->default(false);
            $table->foreignId('parent_id')->nullable()->constrained('comments','comment_id')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('comments');
    }
};
