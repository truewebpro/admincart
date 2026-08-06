<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_earn_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();

            // Groups actions for icons/UI sections. 'order' is informational only —
            // order points are still calculated dynamically via LoyaltySetting/LoyaltyProductPoint,
            // not this table's fixed `points` column.
            $table->enum('category', ['review', 'social_follow', 'social_share', 'order', 'custom']);

            // Which external service this refers to, if any (drives icon + link in UI).
            // Nullable/free-text so admin can add new platforms without a migration.
            $table->string('platform')->nullable(); // e.g. google, trustpilot, facebook, instagram, twitter, onsite

            $table->string('label');          // admin + customer facing title, e.g. "Leave us a review on Trustpilot"
            $table->text('description')->nullable(); // customer-facing helper text
            $table->string('action_url')->nullable(); // link customer is sent to (review page, social profile, etc)

            $table->unsignedInteger('points');

            // automatic: system triggers + awards immediately (e.g. on-site review submitted, order completed)
            // manual_admin: customer claims it, sits in a queue until an admin approves/rejects
            $table->enum('verification', ['automatic', 'manual_admin'])->default('manual_admin');

            // once_per_customer: whole-account one-off (follow us on Instagram)
            // once_per_reference: repeatable, but only once per specific item (review THIS product)
            // unlimited: can be claimed repeatedly with no restriction (rare — use with caution)
            $table->enum('repeat_scope', ['once_per_customer', 'once_per_reference', 'unlimited'])
                ->default('once_per_customer');

            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            $table->index(['shop_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_earn_actions');
    }
};
