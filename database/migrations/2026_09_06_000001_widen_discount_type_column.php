<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * discount_type was created as a DB-level ENUM that never anticipated
 * PricingRuleService's 'bundle'/'volume' rule types — only coupon types.
 * Converting to a plain string avoids this exact class of "new type value
 * needs a migration" problem recurring every time a new discount type is
 * introduced anywhere in the discount/coupon/rule system, matching the
 * same reasoning already applied to badges.position elsewhere.
 *
 * NOTE: ->change() requires doctrine/dbal — if it's not already installed:
 *   composer require doctrine/dbal
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acart_discounts', function (Blueprint $table) {
            $table->string('discount_type', 50)->change();
        });
    }

    public function down(): void
    {
        // Not reverting to the original enum's exact value list here since
        // it was never captured — if you need an exact rollback, pull the
        // list first via: SHOW COLUMNS FROM acart_discounts WHERE Field = 'discount_type';
        Schema::table('acart_discounts', function (Blueprint $table) {
            $table->string('discount_type', 50)->nullable(false)->change();
        });
    }
};
