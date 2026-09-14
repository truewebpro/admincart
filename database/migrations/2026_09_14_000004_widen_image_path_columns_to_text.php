<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->text('path')->change();
            $table->text('thirdparty_url')->nullable()->change();

            // Missed in the first pass — real Shopify filenames run
            // 100+ characters on their own (confirmed from real data),
            // and that length is outside your control since it comes
            // from whatever the merchant/Shopify generated.
            $table->text('filename')->nullable()->change();

            // Same class of risk, lower priority but same fix cost —
            // Shopify's alt text field has no hard length limit on
            // their side either, so a merchant COULD write something
            // unusually long. Widening now avoids ever needing to
            // revisit this column specifically.
            $table->text('alt_text')->nullable()->change();
        });

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

        Schema::table('pages', function (Blueprint $table) {
            $table->text('og_image')->nullable()->change();
        });

        Schema::table('brands', function (Blueprint $table) {
            $table->text('brand_image')->nullable()->change();
        });

        Schema::table('preferences', function (Blueprint $table) {
            $table->text('home_image')->nullable()->change();
            $table->text('shop_logo')->nullable()->change();
        });

        Schema::table('features', function (Blueprint $table) {
            $table->text('fimage')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Deliberately a no-op — see reasoning in the original version
        // of this migration.
    }
};
