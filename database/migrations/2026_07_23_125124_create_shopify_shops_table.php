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
        Schema::create('shopify_shops', function (Blueprint $table) {
            $table->id();
            $table->string('shop_domain')->unique(); // e.g. immyzltd.myshopify.com
            $table->string('client_id');
            $table->text('client_secret'); // encrypted via cast, see model
            $table->text('access_token')->nullable(); // encrypted via cast
            $table->timestamp('token_expires_at')->nullable();
            $table->text('scope')->nullable();
            $table->foreignId('shop_id')->constrained('shops','shop_id')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shopify_shops');
    }
};
