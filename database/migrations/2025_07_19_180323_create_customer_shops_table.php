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
        Schema::create('customer_shops', function (Blueprint $table) {
            $table->id('cshop_id')->index();
            $table->json('ctags')->nullable();
            $table->foreignId('customer_id')->constrained('customers','customer_id')->onDelete('cascade');
            $table->foreignId('shop_id')->constrained('shops','shop_id')->onDelete('cascade');
            $table->enum('status', ['active', 'banned', 'inactive'])->default('active');
            $table->timestamp('registered_at')->nullable();
            $table->timestamps();
            $table->unique(['customer_id', 'shop_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_shops');
    }
};
