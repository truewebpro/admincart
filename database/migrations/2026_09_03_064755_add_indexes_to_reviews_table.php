<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // A unique constraint will fail to apply if duplicate (customer_id, product_id)
        // rows already exist. Since real data may already be in this table, check first
        // rather than let the migration fail mid-way.
        $duplicates = DB::table('reviews')
            ->select('customer_id', 'product_id')
            ->groupBy('customer_id', 'product_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicates->isNotEmpty()) {
            throw new \RuntimeException(
                'Cannot add unique(customer_id, product_id) to reviews: '
                . $duplicates->count()
                . ' customer/product pair(s) already have more than one review. '
                . 'Resolve or delete the duplicates before re-running this migration.'
            );
        }

        Schema::table('reviews', function (Blueprint $table) {
            // One review per customer per product, enforced at the DB level
            $table->unique(['customer_id', 'product_id'], 'reviews_customer_product_unique');

            // Matches: WHERE product_id = ? AND review_status = 'verified'
            $table->index(['product_id', 'review_status'], 'reviews_product_status_index');

            // Matches a shop-wide moderation/admin listing filtered by status
            $table->index(['shop_id', 'review_status'], 'reviews_shop_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_customer_product_unique');
            $table->dropIndex('reviews_product_status_index');
            $table->dropIndex('reviews_shop_status_index');
        });
    }
};
