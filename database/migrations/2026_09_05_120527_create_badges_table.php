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
        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
            $table->string('label');
            $table->boolean('use_label')->default(true);
            $table->string('color',7)->nullable();
            $table->string('bg_color',7)->nullable();
            $table->string('style')->nullable();
            $table->string('position');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['shop_id']);
        });

        Schema::create('badge_coupons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('badge_id')->constrained('badges')->cascadeOnDelete();
            $table->foreignId('coupon_id')->constrained('coupons', 'coupon_id')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['badge_id', 'coupon_id']);
        });

        Schema::create('badge_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('badge_id')->constrained('badges')->cascadeOnDelete();
            $table->foreignId('pricing_rule_id')->constrained('pricing_rules', 'id')->cascadeOnDelete();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['badge_id', 'pricing_rule_id']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('badge_rules');
        Schema::dropIfExists('badge_coupons');
        Schema::dropIfExists('badges');
    }
};
