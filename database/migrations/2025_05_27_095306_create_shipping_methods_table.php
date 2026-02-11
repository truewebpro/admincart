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
        Schema::create('shipping_methods', function (Blueprint $table) {
            $table->id('shipping_method_id')->index();
            $table->string('shipping_method_name')->default('Standard');
            $table->string('shipping_group')->default('Domestic');
            $table->string('delivery_time')->default('3-5 days');
            $table->float('base_cost')->default(2.99);
            $table->boolean('is_active')->default(false);
            $table->foreignId('courier_id')->constrained('couriers','courier_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipping_methods');
    }
};
