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
        Schema::create('scusts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();

            $table->string('thirdparty_id'); // Shopify's customer id, string per your platform-agnostic decision
            $table->string('email')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->string('state')->nullable(); // Shopify's enabled/disabled/invited/declined
            $table->string('tags')->nullable();  // raw comma-separated string, as Shopify sends it
            $table->json('addresses')->nullable(); // full addresses array as returned by Shopify
            $table->timestamp('shopify_created_at')->nullable();

            // Local import tracking: null = not imported yet.
            $table->foreignId('customer_id')->nullable()->constrained('customers', 'customer_id')->nullOnDelete();
            $table->timestamp('imported_at')->nullable();

            $table->timestamps();

            $table->unique(['shop_id', 'thirdparty_id']); // one staging row per shop per Shopify customer

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scusts');
    }
};
