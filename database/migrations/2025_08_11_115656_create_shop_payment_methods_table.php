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
        Schema::create('shop_payment_methods', function (Blueprint $table) {
            $table->id('shop_payment_method_id')->index();
            $table->string('payment_name');
            $table->string('payment_method');
            $table->string('payment_icon');
            $table->json('payment_options')->nullable();
            $table->enum('payment_status', ['active', 'inactive'])->default('active');
            $table->integer('sort_order')->default(0);
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_payment_methods');
    }
};
