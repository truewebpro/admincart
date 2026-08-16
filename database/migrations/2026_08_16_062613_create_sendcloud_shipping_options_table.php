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
        Schema::create('sendcloud_shipping_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sendcloud_id')->constrained('sendclouds')->cascadeOnDelete();
            $table->string('shipping_option_code');   // e.g. "shipping_option:123" — what you send to v3
            $table->unsignedBigInteger('shipping_method_id')->nullable(); // old v2 id, for migration traceability
            $table->string('carrier')->nullable();     // e.g. "DHL"
            $table->string('name');                    // e.g. "DHL Home Delivery"
            $table->string('functionalities')->nullable(); // json: service point, tracked, etc. if you want to filter
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['sendcloud_id', 'shipping_option_code'], 'sco_sendcloud_option_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sendcloud_shipping_options');
    }
};
