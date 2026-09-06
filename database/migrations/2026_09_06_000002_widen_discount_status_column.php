<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Same issue as discount_type: discount_status was built as an ENUM that
 * doesn't include 'applied' (recalculateCart() writes this whenever a
 * rule-sourced discount is currently in effect). discount_source already
 * accepted 'rule' fine, so only this column needs widening.
 *
 * NOTE: ->change() requires doctrine/dbal — if it's not already installed:
 *   composer require doctrine/dbal
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acart_discounts', function (Blueprint $table) {
            $table->string('discount_status', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('acart_discounts', function (Blueprint $table) {
            $table->string('discount_status', 50)->nullable(false)->change();
        });
    }
};
