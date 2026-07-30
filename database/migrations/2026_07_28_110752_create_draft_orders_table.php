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
        Schema::create('draft_orders', function (Blueprint $table) {
            $table->id();

            // Shop scoping — never trust shop_id from the request body.
            // Always stamped server-side from the resolved shop.
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();

            $table->string('draft_number');

            $table->foreignId('customer_id')->nullable()->constrained('customers','customer_id')->nullOnDelete();

            $table->string('status', 20)->default('draft');
            // draft | confirmed | processing | completed | cancelled

            $table->string('payment_status', 20)->default('unpaid');
            // unpaid | partially_paid | paid | refunded

            $table->char('currency', 3)->default('GBP');

            // Monetary fields — always DECIMAL, never float/double.
            $table->decimal('subtotal', 12, 2)->default(0);

            $table->string('discount_type', 20)->nullable(); // fixed | percentage
            $table->decimal('discount_value', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);

            $table->decimal('shipping_total', 12, 2)->default(0);
            $table->decimal('tax_total', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            $table->decimal('amount_paid', 12, 2)->default(0);
            $table->decimal('amount_due', 12, 2)->default(0);

            $table->text('internal_note')->nullable();
            $table->text('customer_note')->nullable();

            $table->string('shipping_method')->nullable();
            // e.g. dpd | royal_mail | collection | manual | free
            $table->string('courier')->nullable();
            $table->date('requested_delivery_date')->nullable();
            $table->boolean('collection_only')->default(false);
            $table->boolean('billing_same_as_shipping')->default(false);

            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('confirmed_at')->nullable();

            // FK to your existing orders table — adjust table name if different.
            $table->foreignId('converted_order_id')->nullable()
                ->constrained('orders','order_id')->nullOnDelete();

            // Optimistic locking for autosave — prevents stale overwrites when
            // two tabs/users edit the same draft concurrently.
            $table->unsignedInteger('version')->default(1);

            $table->timestamps();
            $table->softDeletes();

            $table->unique(['shop_id', 'draft_number']);
            $table->index(['shop_id', 'status']);
            $table->index(['shop_id', 'customer_id']);
            $table->index(['shop_id', 'created_at']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('draft_orders');
    }
};
