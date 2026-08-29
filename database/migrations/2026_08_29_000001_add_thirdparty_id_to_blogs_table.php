<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            // Shopify's article id — used for duplicate detection when
            // an article is created from the live view, same pattern as
            // pages/customers/products.
            $table->string('thirdparty_id')->nullable()->after('shop_id');
            $table->string('thirdparty_blog_id')->nullable()->after('thirdparty_id');
            $table->string('thirdparty_blog_handle')->nullable()->after('thirdparty_blog_id');
            $table->unique(['shop_id', 'thirdparty_id']);
        });
    }

    public function down(): void
    {
        Schema::table('blogs', function (Blueprint $table) {
            $table->dropUnique(['shop_id', 'thirdparty_id']);
            $table->dropColumn('thirdparty_id','thirdparty_blog_id', 'thirdparty_blog_handle');
        });
    }
};
