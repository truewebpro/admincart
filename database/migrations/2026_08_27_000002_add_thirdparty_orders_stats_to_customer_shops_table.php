<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_shops', function (Blueprint $table) {
            // Shopify's own reported figures for this customer, scoped
            // to this shop. Deliberately prefixed thirdparty_ to make
            // clear this is external, Shopify-reported data — not
            // computed from your own orders table, and not treated as
            // your system's source of truth for financials.
            $table->unsignedInteger('thirdparty_orders_count')->default(0)->after('thirdparty_id');
            $table->decimal('thirdparty_spent', 10, 2)->default(0)->after('thirdparty_orders_count');
        });
    }

    public function down(): void
    {
        Schema::table('customer_shops', function (Blueprint $table) {
            $table->dropColumn(['thirdparty_orders_count', 'thirdparty_spent']);
        });
    }
};
