<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_product_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();
            $table->foreignId('variant_id')->constrained('variants', 'variant_id')->cascadeOnDelete();

            // Fixed points awarded PER UNIT of this variant, overriding the shop's
            // global spend_amount/points_per_spend calculation entirely for this variant.
            // e.g. set to 0 to exclude a product from earning points, or set a flat
            // bonus value regardless of price (promotions, clearance, loss-leaders, etc).
            $table->unsignedInteger('points_per_unit');

            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['shop_id', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_product_points');
    }
};
