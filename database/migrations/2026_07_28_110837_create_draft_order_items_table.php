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
        Schema::create('draft_order_items', function (Blueprint $table) {
            $table->id();

            // Denormalised shop_id (matches parent draft_order's shop_id) so
            // scoping/global-scope checks don't require a join to draft_orders.
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();

            $table->foreignId('draft_order_id')->constrained('draft_orders')->cascadeOnDelete();

            // Nullable: custom items have neither.
            $table->foreignId('product_id')->nullable()->constrained('products','product_id')->nullOnDelete();
            $table->foreignId('variant_id')->nullable()
                ->constrained('variants','variant_id')->nullOnDelete();

            $table->string('item_type', 10); // product | custom

            $table->string('title');
            $table->string('variant_title')->nullable();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->string('product_image')->nullable();

            $table->unsignedInteger('quantity');

            // unit_cost captured for "price below cost" warning at add-time.
            $table->decimal('unit_cost', 12, 2)->nullable();
            $table->decimal('unit_price', 12, 2);

            $table->string('discount_type', 20)->nullable(); // fixed | percentage
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);

            $table->decimal('vat_rate', 5, 2)->default(20.00);
            $table->decimal('tax_total', 12, 2)->default(0);

            $table->decimal('line_subtotal', 12, 2);
            $table->decimal('line_total', 12, 2);

            // Stock level captured at the moment the item was added, for audit
            // trail purposes (not used for live availability checks).
            $table->integer('stock_snapshot')->nullable();

            $table->unsignedInteger('sort_order')->default(0);

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['shop_id', 'draft_order_id']);
            $table->index(['shop_id', 'variant_id']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_order_items');
    }
};
