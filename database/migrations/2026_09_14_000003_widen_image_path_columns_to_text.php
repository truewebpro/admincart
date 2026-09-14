<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // media_files.path — the new library's own storage
        Schema::table('media_files', function (Blueprint $table) {
            $table->text('path')->change();
            $table->text('thirdparty_url')->nullable()->change(); // Shopify CDN urls can be long too
        });

        // Every existing flat image column, same risk, same fix
        Schema::table('products', function (Blueprint $table) {
            $table->text('featured_image')->nullable()->change();
        });

        Schema::table('variants', function (Blueprint $table) {
            $table->text('variant_image')->nullable()->change();
        });

        Schema::table('blogs', function (Blueprint $table) {
            $table->text('blog_image')->nullable()->change();
        });

        Schema::table('cats', function (Blueprint $table) {
            $table->text('cat_image')->nullable()->change();
        });

        // pages table has og_image per its schema shown earlier
        Schema::table('pages', function (Blueprint $table) {
            $table->text('og_image')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Deliberately not reverting to varchar(255) — going back
        // would risk silently truncating any path that grew past 255
        // in the meantime. Down() left as a no-op to avoid that danger
        // if this migration is ever rolled back.
    }
};
