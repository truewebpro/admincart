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
        Schema::table('acarts', function (Blueprint $table) {
            $table->foreignId('address_id')
                ->after('customer_id')
                ->nullable()
                ->constrained('customer_addresses', 'address_id')
                ->nullOnDelete();
            $table->decimal('payment_fee', 10, 2)->default(0)->after('payment_method');
            $table->boolean('shipping_protection_enabled')->default(false)->after('shipping_cost');
            $table->decimal('shipping_protection_fee', 10, 2)->default(0)->after('shipping_protection_enabled');
            $table->text('notes')->nullable()->after('shipping_protection_fee');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('acarts', function (Blueprint $table) {
            $table->dropForeign(['address_id']);
            $table->dropColumn([
                'address_id',
                'payment_fee',
                'shipping_protection_enabled',
                'shipping_protection_fee',
                'notes',
            ]);
        });
    }
};
