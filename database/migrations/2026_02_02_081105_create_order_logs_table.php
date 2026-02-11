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
        Schema::create('order_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->nullable()->constrained('orders','order_id')->cascadeOnDelete();
            $table->foreignId('cart_id')->nullable()->constrained('carts','cart_id')->cascadeOnDelete();
            $table->string('event');
            $table->text('description')->nullable();
            $table->json('meta')->nullable();
            $table->string('source')->default('system');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_logs');
    }
};
