<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            // Shopify's own variant id — stable identifier used for
            // cost-price sync, order-item linking, and any future
            // variant-level sync, matching the thirdparty_id convention
            // used across every other table in this build.
            $table->string('thirdparty_id')->nullable()->after('product_id');

            // Scoped by shop_id, matching the convention used on every
            // other thirdparty_id column in this build (products,
            // customers, orders, pages, blogs, collections).
            $table->unique(['shop_id', 'thirdparty_id']);
        });
    }

    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->dropUnique(['shop_id', 'thirdparty_id']);
            $table->dropColumn('thirdparty_id');
        });
    }
};
