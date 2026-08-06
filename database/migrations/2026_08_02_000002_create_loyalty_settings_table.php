<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();

            $table->boolean('is_active')->default(true);

            // Earn rule: "spend £X get Y points". Default X=1, Y=1 => spend £1 get 1 point.
            $table->decimal('spend_amount', 10, 2)->default(1.00);
            $table->unsignedInteger('points_per_spend')->default(1);

            // Optional guard rails
            $table->decimal('min_order_amount_to_earn', 10, 2)->nullable();
            $table->unsignedInteger('max_points_per_order')->nullable();

            $table->timestamps();

            $table->unique('shop_id'); // one settings row per shop
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_settings');
    }
};
