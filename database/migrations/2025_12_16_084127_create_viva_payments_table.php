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
        Schema::create('viva_payments', function (Blueprint $table) {
            $table->id('payment_id')->index();
            $table->bigInteger('order_code');
            $table->string('transaction_id');
            $table->string('status_id');
            $table->decimal('amount', 15, 2);
            $table->string('fullname')->nullable();
            $table->string('email')->nullable();
            $table->json('payload')->nullable();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('viva_payments');
    }
};
