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
        Schema::create('order_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders', 'order_id')->cascadeOnDelete();
            $table->string('status_name');   // raw message from Sendcloud, e.g. "Parcel en route"
            $table->string('status_value')->nullable(); // mapped internal value, e.g. "in_transit"
            $table->string('status_code')->nullable();  // raw code if provided, e.g. "READY_TO_SEND"
            $table->string('parcel_id')->nullable();
            $table->string('carrier')->nullable();
            $table->timestamp('event_at');   // when Sendcloud says the event happened
            $table->timestamps();            // when we recorded it

            $table->index(['order_id', 'event_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_tracking_events');
    }
};
