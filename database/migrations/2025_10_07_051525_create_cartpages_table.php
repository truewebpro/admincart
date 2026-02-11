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
        Schema::create('cartpages', function (Blueprint $table) {
            $table->id('cartpage_id')->index();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->onDelete('cascade');
            $table->string('cart_slug')->default('cart');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cartpages');
    }
};
