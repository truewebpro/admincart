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
        Schema::create('related_cats', function (Blueprint $table) {
            $table->id('related_cat_id')->index();
            $table->foreignId('cat_parent_id')->constrained('cats', 'cat_id')->cascadeOnDelete();
            $table->string('related_cat_title');
            $table->string('related_image')->nullable();
            $table->foreignId('cat_child_id')->constrained('cats', 'cat_id')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('related_cats');
    }
};
