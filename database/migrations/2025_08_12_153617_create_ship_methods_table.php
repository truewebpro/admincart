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
        Schema::create('ship_methods', function (Blueprint $table) {
            $table->id('ship_method_id')->index();
            $table->string('method')->default('Standard (2 to 4 business days');
            $table->float('price')->default(0.00);
            $table->string('zone')->default('Domestic');
            $table->foreignId('courier_id')->constrained('couriers', 'courier_id')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::table('locations', function (Blueprint $table) {
            $table->string('country')->after('location_address')->default('United Kingdom');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ship_methods');
    }
};
