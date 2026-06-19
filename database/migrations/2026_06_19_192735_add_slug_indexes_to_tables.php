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
        Schema::table('products', function (Blueprint $table) {
            $table->index(['shop_id', 'handle'], 'products_shop_handle_idx');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->index(['shop_id', 'brand_slug'], 'brands_shop_slug_idx');
        });

        Schema::table('cats', function (Blueprint $table) {
            $table->index(['shop_id', 'cat_slug'], 'cats_shop_slug_idx');
        });

        Schema::table('blogs', function (Blueprint $table) {
            $table->index(['shop_id', 'blog_slug'], 'blogs_shop_slug_idx');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->index(['shop_id', 'page_slug'], 'pages_shop_slug_idx');
        });

        Schema::table('policies', function (Blueprint $table) {
            $table->index(['shop_id', 'policy_slug'], 'policies_shop_slug_idx');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->index(['shop_id', 'menu_slug'], 'menus_shop_slug_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_shop_handle_idx');
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->dropIndex('brands_shop_slug_idx');
        });

        Schema::table('cats', function (Blueprint $table) {
            $table->dropIndex('cats_shop_slug_idx');
        });

        Schema::table('blogs', function (Blueprint $table) {
            $table->dropIndex('blogs_shop_slug_idx');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex('pages_shop_slug_idx');
        });

        Schema::table('policies', function (Blueprint $table) {
            $table->dropIndex('policies_shop_slug_idx');
        });

        Schema::table('menus', function (Blueprint $table) {
            $table->dropIndex('menus_shop_slug_idx');
        });
    }
};
