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
        Schema::create('acart_events', function (Blueprint $table) {
            $table->id('acart_event_id')->index();
            $table->foreignId('acart_id')->constrained('acarts','acart_id')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops','shop_id');
            //cart_created, item_added, item_removed,quantity_updated,coupon_applied,shipping_selected,checkout_started,cart_converted,cart_abandoned
            $table->string('event_type');
            $table->json('event_data')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->timestamps();
            $table->index(['shop_id', 'event_type']);
            $table->index(['acart_id', 'event_type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('acart_events');
    }
};
