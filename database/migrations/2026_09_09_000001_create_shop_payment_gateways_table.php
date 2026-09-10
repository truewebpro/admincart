<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();
            $table->string('provider', 32);            // viva_wallet | worldpay | cybersource
            $table->string('label')->nullable();       // merchant-facing name
            $table->json('credentials');               // encrypted JSON, shape differs per provider
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->string('environment', 16)->default('sandbox'); // sandbox | production
            $table->timestamps();

            $table->unique(['shop_id', 'provider']);
            $table->index(['shop_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_payment_gateways');
    }
};
