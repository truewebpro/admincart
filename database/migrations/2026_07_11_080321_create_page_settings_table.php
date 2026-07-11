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
        Schema::create('page_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();

            $table->enum('page_type', [
                'homepage',
                'products',
                'cats',
                'brands',
                'blogs',
                'pages',
                'policies',
            ]);
            $table->string('slug')->default('/');
            $table->string('title')->nullable();
            $table->text('description')->nullable();

            // SEO
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();

            $table->string('og_image')->nullable();

            $table->boolean('status')->default(true);

            $table->timestamps();

            $table->unique(['shop_id', 'page_type']);
            $table->unique(['shop_id', 'slug']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('page_settings');
    }
};
