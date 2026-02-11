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
        Schema::table('orders', function (Blueprint $table) {
            $table->string('parcel_id')->after('checkout_id')->nullable();
            $table->string('tracking_number')->after('parcel_id')->nullable();
            $table->string('shipment_id')->after('tracking_number')->nullable();
            $table->string('shipment_name')->after('shipment_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            //
        });
    }
};
