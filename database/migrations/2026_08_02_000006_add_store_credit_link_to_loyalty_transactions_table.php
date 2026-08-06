<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loyalty_transactions', function (Blueprint $table) {
            // When a redeem happens, this points to the store_credit_transaction that was created for it.
            $table->foreignId('store_credit_transaction_id')->nullable()->after('loyalty_redeem_rule_id')
                ->constrained('store_credit_transactions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('loyalty_transactions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('store_credit_transaction_id');
        });
    }
};
