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
        Schema::create('product_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();
            $table->string('label');
            $table->boolean('use_label')->default(true);
            $table->string('color', 7)->nullable();
            $table->string('bg_color', 7)->nullable();
            $table->string('style')->nullable();
            $table->string('image')->nullable();
            $table->string('position');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['shop_id']);
        });

        Schema::create('product_label_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_label_id')->constrained('product_labels')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();
            $table->string('column');     // 'tag' | 'type' | 'vendor' | 'title'
            $table->string('relation');   // 'equals' | 'not_equals' | 'contains' | 'not_contains'
            $table->string('condition');  // the comparison value
            $table->string('join_type')->default('and'); // 'and' | 'or'

            $table->timestamps();

            $table->index(['product_label_id']);
        });

        Schema::create('product_label_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_label_id')->constrained('product_labels')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products', 'product_id')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['product_label_id', 'product_id']);
        });


    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_label_products');
        Schema::dropIfExists('product_label_rules');
        Schema::dropIfExists('product_labels');
    }
};
