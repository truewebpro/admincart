<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Balances live on customer_shops, NOT customers, because a customer's store
     * credit and loyalty points are separate per shop (customers is a global
     * record shared across every shop; customer_shops is the per-shop scope).
     */
    public function up(): void
    {
        Schema::table('customer_shops', function (Blueprint $table) {
            $table->decimal('store_credit_balance', 12, 2)->default(0)->after('cshop_id');
            $table->unsignedInteger('loyalty_points_balance')->default(0)->after('store_credit_balance');
        });
    }

    public function down(): void
    {
        Schema::table('customer_shops', function (Blueprint $table) {
            $table->dropColumn(['store_credit_balance', 'loyalty_points_balance']);
        });
    }
};
