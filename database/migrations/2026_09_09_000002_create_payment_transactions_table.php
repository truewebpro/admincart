<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();

            // Denormalised so every admin query scopes by session shop without
            // joining orders. Also what the idempotency unique index hangs off.
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();

            // nullable() must come before constrained() for nullOnDelete to be legal.
            $table->foreignId('order_id')->nullable()
                ->constrained('orders', 'order_id')->nullOnDelete();

            $table->foreignId('parent_id')->nullable()   // refund/void points at the original charge
            ->constrained('payment_transactions')->nullOnDelete();

            $table->string('provider', 32);
            $table->string('type', 16);                  // charge | refund | void
            $table->string('status', 16);                // pending | succeeded | failed
            $table->string('gateway_transaction_id')->nullable();

            $table->unsignedBigInteger('amount_minor');  // always minor units (cents)
            $table->char('currency', 3);

            $table->string('idempotency_key', 64)->nullable();
            $table->string('reason')->nullable();
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();

            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            // Provider-specific handles we must keep from the original charge.
            // Worldpay Access, for example, only lets you refund via the action
            // links returned on the payment response.
            $table->json('meta')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->unique(['shop_id', 'idempotency_key']);
            $table->index(['shop_id', 'order_id']);
            $table->index(['provider', 'gateway_transaction_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
