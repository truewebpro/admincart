<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cats', function (Blueprint $table) {
            // Shopify's collection id — covers BOTH custom_collections
            // (cat_type = 'manual') and smart_collections
            // (cat_type = 'smart'), since both live in this same table
            // distinguished by cat_type.
            $table->string('thirdparty_id')->nullable()->after('shop_id');

            // Preserved separately from cat_slug — if the local slug
            // ever gets edited after import, this still holds the
            // ORIGINAL Shopify handle, needed to reconstruct
            // /collections/{handle} for redirects later (same reasoning
            // as thirdparty_blog_handle on articles).
            $table->string('thirdparty_handle')->nullable()->after('thirdparty_id');

            $table->unique(['shop_id', 'thirdparty_id']);
        });
    }

    public function down(): void
    {
        Schema::table('cats', function (Blueprint $table) {
            $table->dropUnique(['shop_id', 'thirdparty_id']);
            $table->dropColumn(['thirdparty_id', 'thirdparty_handle']);
        });
    }
};
