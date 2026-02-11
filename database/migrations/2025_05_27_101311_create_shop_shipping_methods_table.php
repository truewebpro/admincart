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
        Schema::create('shop_shipping_methods', function (Blueprint $table) {
            $table->id('shop_shipping_method_id')->index();
            $table->foreignId('shipping_method_id')->constrained('shipping_methods', 'shipping_method_id');
            $table->float('custom_cost')->nullable();
            $table->float('handling_fee')->nullable();
            $table->boolean('is_enabled')->default(false);
            $table->tinyInteger('priority')->default(0);
            $table->foreignId('shop_id')->constrained('shops','shop_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_shipping_methods');
    }
};
