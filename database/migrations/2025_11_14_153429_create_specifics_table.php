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
        Schema::create('specifics', function (Blueprint $table) {
            $table->id('specific_id')->index();
            $table->string('stitle')->default('Brand');
            $table->string('svalue')->nullable();
            $table->string('sicon')->nullable();
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
        Schema::dropIfExists('specifics');
    }
};
