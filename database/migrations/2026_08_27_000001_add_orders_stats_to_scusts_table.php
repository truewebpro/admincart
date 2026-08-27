<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scusts', function (Blueprint $table) {
            // Shopify's own reported figures, straight from the customer
            // object — NOT computed from your local orders table. These
            // reflect the customer's full Shopify order history, which
            // may be more than what's actually been imported locally.
            $table->unsignedInteger('orders_count')->default(0)->after('state');
            $table->decimal('total_spent', 10, 2)->default(0)->after('orders_count');
        });
    }

    public function down(): void
    {
        Schema::table('scusts', function (Blueprint $table) {
            $table->dropColumn(['orders_count', 'total_spent']);
        });
    }
};
