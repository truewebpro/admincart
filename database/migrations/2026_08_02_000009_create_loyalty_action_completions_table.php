<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loyalty_action_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained('shops', 'shop_id')->cascadeOnDelete();
            $table->foreignId('cshop_id')->constrained('customer_shops', 'cshop_id')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers', 'customer_id')->cascadeOnDelete(); // denormalized
            $table->foreignId('loyalty_earn_action_id')->constrained()->cascadeOnDelete();

            // Scopes a claim to a specific item for 'once_per_reference' actions,
            // e.g. reference_type='product', reference_id=123 for "review this product".
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();

            // For manual_admin actions: optional link/screenshot the customer submits as proof
            // (e.g. a link to their Google review) for the admin to check.
            $table->string('proof_url')->nullable();

            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedInteger('points_awarded')->nullable(); // filled once approved
            $table->foreignId('loyalty_transaction_id')->nullable()
                ->constrained('loyalty_transactions')->nullOnDelete();

            $table->text('admin_notes')->nullable();
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();

            $table->timestamps();

            $table->index(['shop_id', 'cshop_id']);
            $table->index(['shop_id', 'status']);
            $table->index(['loyalty_earn_action_id', 'reference_type', 'reference_id'], 'lac_action_reference_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_action_completions');
    }
};
