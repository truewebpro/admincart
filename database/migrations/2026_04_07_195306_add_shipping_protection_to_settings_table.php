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
        Schema::table('settings', function (Blueprint $table) {
            $table->boolean('shipping_protection_enabled')->default(false)->after('hide_price');
            $table->double('shipping_protection_fee')->default(0)->after('shipping_protection_enabled');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->double('payment_fee')->default(0)->after('placed_at');
            $table->double('shipping_protection_fee')->default(0)->after('payment_fee');
        });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            //
        });
    }
};
