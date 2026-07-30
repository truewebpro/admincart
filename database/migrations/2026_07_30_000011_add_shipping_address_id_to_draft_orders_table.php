<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tracks which real CustomerAddress was chosen for delivery — the
     * existing shipping_address/billing_address JSON columns are a
     * point-in-time snapshot (so the order isn't retroactively affected
     * if the customer edits their address later), but conversion needs
     * to populate orders.address_id with a real FK, not just a JSON blob.
     */
    public function up(): void
    {
        Schema::table('draft_orders', function (Blueprint $table) {
            $table->foreignId('shipping_address_id')
                ->nullable()
                ->constrained('customer_addresses', 'address_id')
                ->after('shipping_address')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('draft_orders', function (Blueprint $table) {
            $table->dropForeign(['shipping_address_id']);
            $table->dropColumn('shipping_address_id');
        });
    }
};
