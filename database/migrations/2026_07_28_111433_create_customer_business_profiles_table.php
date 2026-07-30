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
        Schema::create('customer_business_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
            $table->foreignId('cshop_id')->constrained('customer_shops', 'cshop_id')->cascadeOnDelete();

            $table->string('business_name')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('customer_group')->nullable(); // e.g. Wholesale, Retail — normalise to a lookup table later if these need admin management
            $table->decimal('credit_limit', 12, 2)->default(0);

            // Cached/synced balance — the authoritative source of truth is
            // unpaid confirmed orders; this column is a denormalised total
            // kept up to date by a service/listener, not hand-edited.
            $table->decimal('outstanding_balance', 12, 2)->default(0);

            $table->timestamps();

            $table->unique('cshop_id');
            $table->index(['shop_id', 'business_name']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_business_profiles');
    }
};
