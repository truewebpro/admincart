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
        Schema::table('shop_payment_methods', function (Blueprint $table) {
            $table->decimal('handling_fee', 10, 2)->default(0)->after('payment_icon');
            $table->enum('fee_type', ['fixed', 'percentage'])->default('fixed')->after('handling_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shop_payment_methods', function (Blueprint $table) {
            //
        });
    }
};
